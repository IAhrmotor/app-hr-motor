<?php

namespace Tests\Feature\Users;

use App\Models\Dealership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSalesforceIdTest extends TestCase
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

    public function test_admin_can_create_non_commercial_user_without_salesforce_id(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Gestor HR',
            'email' => 'gestor@example.com',
            'role' => 'gestor',
        ]));

        $createdUser = User::where('email', 'gestor@example.com')->first();

        $response->assertRedirect(route('users.index'));
        $this->assertNotNull($createdUser);
        $this->assertNull($createdUser->salesforce_user_id);
        $this->assertNull($createdUser->dealership);
        $this->assertNull($createdUser->dealership_id);
    }

    public function test_commercial_user_requires_salesforce_id_when_created(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Torrejón',
        ]);

        $response = $this->from(route('users.create'))->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Comercial Sin Salesforce',
            'email' => 'comercial-sf@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => '',
            'dealership_id' => $dealership->id,
        ]));

        $response
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('salesforce_user_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'comercial-sf@example.com',
        ]);
    }

    public function test_store_manager_user_requires_salesforce_id_when_created(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Torrejon',
        ]);

        $response = $this->from(route('users.create'))->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Jefe Tienda Sin Salesforce',
            'email' => 'jefe-tienda-sf@example.com',
            'role' => User::ROLE_USER,
            'is_store_manager' => '1',
            'salesforce_user_id' => '',
            'dealership_id' => $dealership->id,
        ]));

        $response
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('salesforce_user_id');
    }

    public function test_commercial_user_requires_salesforce_id_when_updated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create();

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-USER-003',
            'dealership' => $dealership->name,
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->from(route('users.edit', $user))->actingAs($admin)->put(route('users.update', $user), $this->baseUserPayload([
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => '',
            'dealership_id' => $dealership->id,
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors('salesforce_user_id');
    }

    public function test_commercial_user_can_be_created_without_dealership(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Comercial Sin Delegacion',
            'email' => 'comercial-sin-delegacion@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-USER-004',
            'dealership_id' => '',
        ]));

        $response->assertRedirect(route('users.index'));

        $createdUser = User::where('email', 'comercial-sin-delegacion@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertNull($createdUser->dealership);
        $this->assertNull($createdUser->dealership_id);
    }

    public function test_commercial_user_can_be_updated_without_dealership(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Pamplona',
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-USER-003',
            'dealership' => 'Pamplona',
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), $this->baseUserPayload([
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-USER-003',
            'dealership_id' => '',
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertNull($user->dealership);
        $this->assertNull($user->dealership_id);
    }

    public function test_salesforce_id_is_cleared_when_user_stops_being_commercial(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create();

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-USER-002',
            'dealership' => $dealership->name,
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), $this->baseUserPayload([
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'gestor',
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('gestor', $user->role);
        $this->assertNull($user->salesforce_user_id);
        $this->assertNull($user->dealership);
        $this->assertNull($user->dealership_id);
    }

    public function test_user_can_be_promoted_to_store_manager_while_keeping_commercial_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Bilbao',
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-USER-STORE-001',
            'dealership' => 'Bilbao',
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), $this->baseUserPayload([
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_USER,
            'is_store_manager' => '1',
            'salesforce_user_id' => 'SF-USER-STORE-001',
            'dealership_id' => $dealership->id,
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertSame(User::ROLE_STORE_MANAGER, $user->extra_role);
        $this->assertSame('SF-USER-STORE-001', $user->salesforce_user_id);
        $this->assertSame('Bilbao', $user->dealership);
        $this->assertSame($dealership->id, $user->dealership_id);
    }
}
