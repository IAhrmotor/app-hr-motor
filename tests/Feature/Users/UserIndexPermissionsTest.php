<?php

namespace Tests\Feature\Users;

use App\Models\AdminPermissionGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_with_manage_users_permission_sees_actions_for_manageable_users(): void
    {
        $viewer = User::factory()->create([
            'name' => 'Gestor Con Permiso',
            'role' => User::ROLE_USER,
            'extra_role' => null,
            'email' => 'gestor-permiso@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'users.manage',
            'user_id' => $viewer->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $manageableUser = User::factory()->create([
            'name' => 'Usuario Comercial',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'email' => 'comercial@example.com',
        ]);

        $nonManageableUser = User::factory()->create([
            'name' => 'Usuario Normal',
            'role' => User::ROLE_USER,
            'extra_role' => null,
            'email' => 'normal@example.com',
        ]);

        $response = $this->actingAs($viewer)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee(route('users.edit', $manageableUser), false)
            ->assertSee('Editar usuario', false)
            ->assertSee('Desactivar usuario', false)
            ->assertDontSee(route('users.edit', $nonManageableUser), false);
    }
}
