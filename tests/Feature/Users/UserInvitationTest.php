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

        Notification::assertSentTo($createdUser, ResetPassword::class);
    }
}
