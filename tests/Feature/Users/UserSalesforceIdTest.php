<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSalesforceIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_non_commercial_user_without_salesforce_id(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Gestor HR',
            'email' => 'gestor@example.com',
            'role' => 'gestor',
        ]);

        $createdUser = User::where('email', 'gestor@example.com')->first();

        $response->assertRedirect(route('users.index'));
        $this->assertNotNull($createdUser);
        $this->assertNull($createdUser->salesforce_user_id);
        $this->assertNull($createdUser->dealership);
    }

    public function test_commercial_user_requires_salesforce_id_when_created(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->from(route('users.create'))->actingAs($admin)->post(route('users.store'), [
            'name' => 'Comercial Sin Salesforce',
            'email' => 'comercial-sf@example.com',
            'role' => 'comercial',
            'salesforce_user_id' => '',
            'dealership' => 'Torrejón',
        ]);

        $response
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('salesforce_user_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'comercial-sf@example.com',
        ]);
    }

    public function test_commercial_user_requires_salesforce_id_when_updated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-USER-003',
        ]);

        $response = $this->from(route('users.edit', $user))->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'comercial',
            'salesforce_user_id' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors('salesforce_user_id');
    }

    public function test_commercial_user_requires_dealership_when_created(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->from(route('users.create'))->actingAs($admin)->post(route('users.store'), [
            'name' => 'Comercial Sin Delegacion',
            'email' => 'comercial-sin-delegacion@example.com',
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-USER-004',
            'dealership' => '',
        ]);

        $response
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('dealership');
    }

    public function test_commercial_user_requires_dealership_when_updated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-USER-003',
            'dealership' => 'Pamplona',
        ]);

        $response = $this->from(route('users.edit', $user))->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-USER-003',
            'dealership' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors('dealership');
    }

    public function test_salesforce_id_is_cleared_when_user_stops_being_commercial(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-USER-002',
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'gestor',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('gestor', $user->role);
        $this->assertNull($user->salesforce_user_id);
        $this->assertNull($user->dealership);
    }
}
