<?php

namespace Tests\Feature\Users;

use App\Models\Dealership;
use App\Models\User;
use App\Notifications\UserOnboardingNotification;
use App\Notifications\UserWelcomeNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function baseUserPayload(array $overrides = []): array
    {
        return array_merge([
            'company_entry_date' => '2026-01-01',
            'job_position' => 'Puesto base',
        ], $overrides);
    }

    private function assertOnboardingNotificationsWereSentTo(User $user): void
    {
        Notification::assertSentTo($user, UserOnboardingNotification::class, function (UserOnboardingNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserOnboardingNotification::TYPE_FORUM
                && $data['link_url'] === route('forum.index');
        });

        Notification::assertSentTo($user, UserOnboardingNotification::class, function (UserOnboardingNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserOnboardingNotification::TYPE_AGENDA
                && $data['link_url'] === route('agenda.index');
        });

        Notification::assertSentTo($user, UserOnboardingNotification::class, function (UserOnboardingNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserOnboardingNotification::TYPE_SALES_RANKING
                && $data['link_url'] === route('leaderboard.sales');
        });

        Notification::assertSentTo($user, UserOnboardingNotification::class, function (UserOnboardingNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserOnboardingNotification::TYPE_VEHICLE_RANKING
                && $data['link_url'] === route('leaderboard.vehicles');
        });

        Notification::assertSentTo($user, UserOnboardingNotification::class, function (UserOnboardingNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserOnboardingNotification::TYPE_WEB
                && $data['link_url'] === route('tools.web');
        });

        Notification::assertSentTo($user, UserOnboardingNotification::class, function (UserOnboardingNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserOnboardingNotification::TYPE_CHAT
                && $data['link_url'] === route('chat.beta');
        });

        Notification::assertSentTo($user, UserOnboardingNotification::class, function (UserOnboardingNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserOnboardingNotification::TYPE_VIDEOS
                && $data['link_url'] === route('videos');
        });
    }

    private function assertWelcomeNotificationWasSentTo(User $user): void
    {
        Notification::assertSentTo($user, UserWelcomeNotification::class, function (UserWelcomeNotification $notification, array $channels, User $notifiable): bool {
            $data = $notification->toDatabase($notifiable);

            return $data['type'] === UserWelcomeNotification::TYPE
                && $data['priority'] === true
                && $data['link_url'] === route('home');
        });
    }

    public function test_admin_can_create_an_inactive_user_and_send_invitation_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Torrejon',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Usuario Invitado',
            'email' => 'invitado@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-USER-001',
            'dealership_id' => $dealership->id,
        ]));

        $createdUser = User::where('email', 'invitado@example.com')->first();

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertNotNull($createdUser);
        $this->assertFalse($createdUser->is_active);
        $this->assertTrue($createdUser->must_change_password);
        $this->assertNull($createdUser->activated_at);
        $this->assertSame('SF-USER-001', $createdUser->salesforce_user_id);
        $this->assertSame('Torrejon', $createdUser->dealership);
        $this->assertSame($dealership->id, $createdUser->dealership_id);
        $this->assertSame(User::DEFAULT_AVATAR_PATH, $createdUser->avatar_path);

        Notification::assertSentTo($createdUser, ResetPassword::class);
        $this->assertWelcomeNotificationWasSentTo($createdUser);
        $this->assertOnboardingNotificationsWereSentTo($createdUser);
    }

    public function test_admin_can_create_a_store_manager_user_from_the_commercial_form(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Bilbao',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Jefe Tienda',
            'email' => 'jefe.tienda@example.com',
            'role' => User::ROLE_USER,
            'is_store_manager' => '1',
            'salesforce_user_id' => 'SF-STORE-001',
            'dealership_id' => $dealership->id,
        ]));

        $createdUser = User::where('email', 'jefe.tienda@example.com')->first();

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertNotNull($createdUser);
        $this->assertSame(User::ROLE_USER, $createdUser->role);
        $this->assertSame(User::ROLE_STORE_MANAGER, $createdUser->extra_role);
        $this->assertSame('SF-STORE-001', $createdUser->salesforce_user_id);
        $this->assertSame('Bilbao', $createdUser->dealership);
        $this->assertSame($dealership->id, $createdUser->dealership_id);

        Notification::assertSentTo($createdUser, ResetPassword::class);
        $this->assertWelcomeNotificationWasSentTo($createdUser);
        $this->assertOnboardingNotificationsWereSentTo($createdUser);
    }

    public function test_admin_can_create_an_area_manager_user_and_it_receives_the_onboarding_notifications(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Area Manager',
            'email' => 'area.manager@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_AREA_MANAGER,
        ]));

        $createdUser = User::where('email', 'area.manager@example.com')->first();

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertNotNull($createdUser);
        Notification::assertSentTo($createdUser, ResetPassword::class);
        $this->assertWelcomeNotificationWasSentTo($createdUser);
        $this->assertOnboardingNotificationsWereSentTo($createdUser);
    }

    public function test_admin_can_create_a_plain_user_and_it_receives_the_welcome_notification(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Usuario Normal',
            'email' => 'usuario.normal@example.com',
            'role' => User::ROLE_USER,
        ]));

        $createdUser = User::where('email', 'usuario.normal@example.com')->first();

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertNotNull($createdUser);
        Notification::assertSentTo($createdUser, ResetPassword::class);
        $this->assertWelcomeNotificationWasSentTo($createdUser);
        Notification::assertNotSentTo($createdUser, UserOnboardingNotification::class);
    }

    public function test_admin_can_resend_invitation_email_to_pending_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pendingUser = User::factory()->create([
            'is_active' => false,
            'must_change_password' => true,
            'activated_at' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('users.resend-invitation', $pendingUser));

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'Correo de activacion reenviado correctamente.');

        Notification::assertSentTo($pendingUser, ResetPassword::class);
    }

    public function test_admin_cannot_resend_invitation_email_to_active_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $activeUser = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('users.resend-invitation', $activeUser));

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', 'Solo puedes reenviar la invitacion a usuarios pendientes de activacion.');

        Notification::assertNothingSent();
    }

    public function test_admin_sees_a_controlled_error_when_invitation_email_cannot_be_sent_on_user_creation(): void
    {
        Password::shouldReceive('broker->sendResetLink')
            ->once()
            ->andThrow(new RuntimeException('SMTP recipient rejected'));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->from(route('users.create'))
            ->actingAs($admin)
            ->post(route('users.store'), $this->baseUserPayload([
                'name' => 'Usuario Fallido',
                'email' => 'test@tesstgsetgeatghaethaethbt.com',
                'role' => 'gestor',
            ]));

        $response
            ->assertRedirect(route('users.create'))
            ->assertSessionHas('error', 'No se ha podido enviar el correo de activacion. Revisa que el email sea correcto y que el dominio exista.');

        $this->assertDatabaseMissing('users', [
            'email' => 'test@tesstgsetgeatghaethaethbt.com',
        ]);
    }

    public function test_admin_sees_a_controlled_error_when_resending_invitation_email_fails(): void
    {
        Password::shouldReceive('broker->sendResetLink')
            ->once()
            ->andThrow(new RuntimeException('SMTP recipient rejected'));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pendingUser = User::factory()->create([
            'is_active' => false,
            'must_change_password' => true,
            'activated_at' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('users.resend-invitation', $pendingUser));

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', 'No se ha podido enviar el correo de activacion. Revisa que el email sea correcto y que el dominio exista.');
    }

    public function test_users_index_displays_the_user_avatar_next_to_the_name(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'name' => 'Usuario Con Avatar',
            'avatar_path' => User::DEFAULT_AVATAR_PATH,
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee('Usuario Con Avatar')
            ->assertSee($user->avatar_url, false);
    }

    public function test_users_index_displays_the_user_dealership_column(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Pamplona',
        ]);

        User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'dealership' => 'Pamplona',
            'dealership_id' => $dealership->id,
            'salesforce_user_id' => 'SF-PAM-001',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee('Delegaci')
            ->assertSee('Pamplona');
    }

    public function test_users_index_displays_expired_status_for_pending_users_with_expired_invitation(): void
    {
        Carbon::setTestNow(now());

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Usuario Caducado',
            'is_active' => false,
            'must_change_password' => true,
            'created_at' => now()->subMinutes(config('auth.passwords.users.expire', 60) + 1),
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee('Usuario Caducado')
            ->assertSee('Caducado');

        Carbon::setTestNow();
    }
}
