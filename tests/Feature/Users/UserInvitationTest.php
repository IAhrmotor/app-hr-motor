<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_inactive_user_and_send_invitation_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Usuario Invitado',
            'email' => 'invitado@example.com',
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-USER-001',
            'dealership' => 'Torrejón',
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
        $this->assertSame(User::DEFAULT_AVATAR_PATH, $createdUser->avatar_path);

        Notification::assertSentTo($createdUser, ResetPassword::class);
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

        User::factory()->create([
            'role' => 'comercial',
            'dealership' => 'Pamplona',
            'salesforce_user_id' => 'SF-PAM-001',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee('Delegación')
            ->assertSee('Pamplona')
            ->assertSee('SF-PAM-001');
    }
}
