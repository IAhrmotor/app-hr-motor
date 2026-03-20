<?php

namespace Tests\Feature\Users;

use App\Models\Dealership;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_create_an_inactive_user_and_send_invitation_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Torrejón',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Usuario Invitado',
            'email' => 'invitado@example.com',
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-USER-001',
            'dealership_id' => $dealership->id,
        ]);

        $createdUser = User::where('email', 'invitado@example.com')->first();

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertNotNull($createdUser);
        $this->assertFalse($createdUser->is_active);
        $this->assertTrue($createdUser->must_change_password);
        $this->assertNull($createdUser->activated_at);
        $this->assertSame('SF-USER-001', $createdUser->salesforce_user_id);
        $this->assertSame('Torrejón', $createdUser->dealership);
        $this->assertSame($dealership->id, $createdUser->dealership_id);
        $this->assertSame(User::DEFAULT_AVATAR_PATH, $createdUser->avatar_path);

        Notification::assertSentTo($createdUser, ResetPassword::class);
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
            ->assertSessionHas('success', 'Correo de activación reenviado correctamente.');

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
            ->assertSessionHas('error', 'Solo puedes reenviar la invitación a usuarios pendientes de activación.');

        Notification::assertNothingSent();
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
            'role' => 'comercial',
            'dealership' => 'Pamplona',
            'dealership_id' => $dealership->id,
            'salesforce_user_id' => 'SF-PAM-001',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee('Delegación')
            ->assertSee('Pamplona')
            ->assertSee('SF-PAM-001');
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
