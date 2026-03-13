<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@example.com',
            'password' => 'password',
            'is_active' => false,
            'must_change_password' => true,
            'activated_at' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasErrors([
                'email' => 'Tu cuenta aún no está activada. Revisa el correo de bienvenida y cambia tu contraseña antes de acceder.',
            ]);

        $this->assertGuest();
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'activo@example.com',
            'password' => 'password',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}
