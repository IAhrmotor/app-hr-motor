<?php

namespace Tests\Feature;

use App\Models\AdminPermissionGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_open_the_permissions_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('Permisos')
            ->assertSee('Grupos por')
            ->assertSee('Usuarios concretos');
    }

    public function test_admin_without_ticket_tool_permission_does_not_see_ticket_tools_in_admin_index(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-no-ticket-tools@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee(route('admin.ticket-tools.index'), false);
    }

    public function test_user_with_ticket_tool_permission_sees_ticket_tools_in_admin_index(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'email' => 'ticket-tools-enabled@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $user->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee(route('admin.ticket-tools.index'), false)
            ->assertSee('Tipos de incidencia', false);
    }

    public function test_non_admin_with_a_group_permission_can_open_the_admin_panel_and_the_assigned_tool(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'email' => 'usuario-permitido@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'users.manage',
            'user_id' => null,
            'group_id' => null,
            'group_role' => User::ROLE_COMMERCIAL,
            'granted_by_user_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee(route('users.index'), false)
            ->assertSee('Admin');

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_admin_can_assign_permissions_to_a_user_and_a_group_role(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_MARKETING,
            'email' => 'marketing@example.com',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.permissions.targets.sync'), [
                'target_type' => 'user',
                'target_user_id' => $user->id,
                'permission_keys' => ['users.manage', 'contacts.manage'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_permission_grants', [
            'user_id' => $user->id,
            'group_id' => null,
            'group_role' => null,
            'permission_key' => 'users.manage',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.permissions.targets.sync'), [
                'target_type' => 'group',
                'target_role' => User::ROLE_COMMERCIAL,
                'permission_keys' => ['users.manage'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_permission_grants', [
            'user_id' => null,
            'group_id' => null,
            'group_role' => User::ROLE_COMMERCIAL,
            'permission_key' => 'users.manage',
        ]);
    }

    public function test_permissions_page_searches_users_across_all_pages(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-search@example.com',
        ]);

        User::factory()->count(15)->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
        ]);

        User::factory()->create([
            'name' => 'Usuario Buscado',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'email' => 'buscado@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.permissions.index', ['users_search' => 'Buscado']))
            ->assertOk()
            ->assertSee('Usuario Buscado');
    }

    public function test_non_admins_cannot_open_the_permissions_logs_page(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'email' => 'gestor@example.com',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.permission-logs.index'))
            ->assertForbidden();
    }
}
