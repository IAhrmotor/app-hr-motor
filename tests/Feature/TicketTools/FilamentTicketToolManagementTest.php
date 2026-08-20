<?php

namespace Tests\Feature\TicketTools;

use App\Filament\Resources\TicketTools\Pages\CreateTicketTool;
use App\Filament\Resources\TicketTools\Pages\EditTicketTool;
use App\Filament\Resources\TicketTools\Pages\ListTicketToolLogs;
use App\Filament\Resources\TicketTools\TicketToolResource;
use App\Models\AdminPermissionGrant;
use App\Models\TicketTool;
use App\Models\TicketToolActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentTicketToolManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_with_permission_can_open_ticket_tools_in_filament(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-ticket-tools@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $admin->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $tool = TicketTool::query()->create([
            'name' => 'Salesforce',
            'color' => '#1d4ed8',
        ]);

        $this->actingAs($admin)
            ->get(TicketToolResource::getUrl())
            ->assertOk()
            ->assertSee('Tipos de incidencia')
            ->assertSee('Salesforce')
            ->assertSee('#1D4ED8', false)
            ->assertSee('background-color:#1d4ed8', false);

        $this->assertDatabaseHas('ticket_tools', [
            'id' => $tool->id,
            'name' => 'Salesforce',
            'color' => '#1d4ed8',
        ]);
    }

    public function test_admin_can_create_ticket_tool_in_filament_and_it_is_logged(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-ticket-tools@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $admin->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(CreateTicketTool::class)
            ->set('data.name', 'Mecánica')
            ->set('data.color', '#e11d48')
            ->call('create')
            ->assertHasNoErrors();

        $tool = TicketTool::query()->where('name', 'Mecánica')->firstOrFail();

        $log = TicketToolActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(TicketToolActivityLog::ACTION_CREATED, $log->action);
        $this->assertSame('Mecánica', $log->target_name);
        $this->assertSame('#e11d48', $log->target_color);
        $this->assertSame([
            'name' => ['from' => null, 'to' => 'Mecánica'],
            'color' => ['from' => null, 'to' => '#e11d48'],
        ], $log->changes);
        $this->assertSame('#e11d48', $tool->color);
    }

    public function test_old_admin_ticket_tool_logs_routes_are_removed(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-ticket-tools@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/logs/herramientas-tickets')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/logs/herramientas-tickets/descargar')
            ->assertNotFound();
    }

    public function test_filament_ticket_tool_changes_appear_in_the_filament_logs_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-ticket-tools@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $admin->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(CreateTicketTool::class)
            ->set('data.name', 'Conexion Log')
            ->set('data.color', '#14b8a6')
            ->call('create')
            ->assertHasNoErrors();

        $this->actingAs($admin)
            ->get(TicketToolResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Logs de tipos de incidencia')
            ->assertSee('Conexion Log')
            ->assertSee('#14b8a6', false)
            ->assertSee('Alta');
    }

    public function test_admin_sees_ticket_tool_logs_button_and_can_open_filament_logs_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-ticket-tools@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $admin->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $tool = TicketTool::query()->create([
            'name' => 'Sistema de color',
            'color' => '#14b8a6',
        ]);

        TicketToolActivityLog::query()->create([
            'action' => TicketToolActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_ticket_tool_id' => $tool->id,
            'target_name' => 'Sistema de color',
            'target_color' => '#14b8a6',
            'changes' => [
                'name' => ['from' => null, 'to' => 'Sistema de color'],
                'color' => ['from' => null, 'to' => '#14b8a6'],
            ],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(TicketToolResource::getUrl())
            ->assertOk()
            ->assertSee('Ver logs', false);

        $this->actingAs($admin)
            ->get(TicketToolResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Logs de tipos de incidencia')
            ->assertSee('Sistema de color')
            ->assertSee('#14b8a6', false);
    }

    public function test_manager_cannot_see_or_open_ticket_tool_logs_in_filament(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'email' => 'gestor-ticket-tools@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $manager->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $this->actingAs($manager)
            ->get(TicketToolResource::getUrl())
            ->assertOk()
            ->assertDontSee('Ver logs', false);

        $this->actingAs($manager)
            ->get(TicketToolResource::getUrl('logs'))
            ->assertForbidden();
    }

    public function test_admin_can_update_ticket_tool_in_filament_and_it_is_logged(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-ticket-tools@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $admin->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $tool = TicketTool::query()->create([
            'name' => 'Soporte',
            'color' => '#1d4ed8',
        ]);

        Livewire::actingAs($admin);

        Livewire::test(EditTicketTool::class, ['record' => $tool->getKey()])
            ->set('data.name', 'Soporte IT')
            ->set('data.color', '#e11d48')
            ->call('save', false, false)
            ->assertHasNoErrors();

        $tool->refresh();

        $log = TicketToolActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(TicketToolActivityLog::ACTION_UPDATED, $log->action);
        $this->assertSame('Soporte IT', $log->target_name);
        $this->assertSame('#e11d48', $log->target_color);
        $this->assertSame([
            'Nombre' => ['from' => 'Soporte', 'to' => 'Soporte IT'],
            'Color' => ['from' => '#1d4ed8', 'to' => '#e11d48'],
        ], $log->changes);
        $this->assertSame('Soporte IT', $tool->name);
        $this->assertSame('#e11d48', $tool->color);
    }

    public function test_admin_can_delete_ticket_tool_in_filament_and_it_is_logged(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-ticket-tools@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'ticket-tools.manage',
            'user_id' => $admin->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $tool = TicketTool::query()->create([
            'name' => 'Baja',
            'color' => '#e11d48',
        ]);

        Livewire::actingAs($admin);

        Livewire::test(EditTicketTool::class, ['record' => $tool->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('ticket_tools', [
            'id' => $tool->id,
        ]);

        $log = TicketToolActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(TicketToolActivityLog::ACTION_DELETED, $log->action);
        $this->assertSame('Baja', $log->target_name);
        $this->assertSame('#e11d48', $log->target_color);
        $this->assertSame([
            'name' => ['from' => 'Baja', 'to' => null],
            'color' => ['from' => '#e11d48', 'to' => null],
        ], $log->changes);
    }
}
