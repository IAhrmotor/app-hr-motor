<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ChatGroups\ChatGroupResource;
use App\Models\CompanyChatConversation;
use App\Models\CompanyChatGroup;
use App\Models\CompanyChatGroupActivityLog;
use App\Models\CompanyChatMessage;
use App\Models\User;
use App\Services\CompanyChatGroupActivityLogFormatter;
use App\Services\CompanyChatGroupManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_open_the_filament_group_resource(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(ChatGroupResource::getUrl())
            ->assertOk()
            ->assertSee('Grupos')
            ->assertSee('Ver logs');
    }

    public function test_admin_can_open_the_group_form_with_name_only_user_search(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['name' => 'Nombre Visible', 'email' => 'privado@example.com']);

        $this->actingAs($admin)
            ->get(ChatGroupResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Buscar usuario por nombre y apellidos')
            ->assertSee('hr-chat-group-members')
            ->assertSee('hr-chat-group-member__remove')
            ->assertSee('Nombre Visible')
            ->assertDontSee('privado@example.com');
    }

    public function test_non_admin_cannot_open_the_group_resource(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(ChatGroupResource::getUrl())
            ->assertForbidden();
    }

    public function test_group_logs_page_is_admin_only_and_records_one_update_with_member_details(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $first = User::factory()->create(['name' => 'Primer miembro']);
        $second = User::factory()->create(['name' => 'Segundo miembro']);
        $replacement = User::factory()->create(['name' => 'Miembro nuevo']);
        $service = app(CompanyChatGroupManagementService::class);

        $group = $service->create('Grupo auditable', [$first->id, $second->id], $admin);
        $service->update($group, 'Grupo auditable editado', [$first->id, $replacement->id], $admin);

        $this->assertSame(2, CompanyChatGroupActivityLog::query()->count());
        $updateLog = CompanyChatGroupActivityLog::query()->where('action', CompanyChatGroupActivityLog::ACTION_UPDATED)->sole();
        $this->assertSame($group->id, $updateLog->company_chat_group_id);
        $this->assertArrayHasKey('participants_added', $updateLog->changes);
        $this->assertArrayHasKey('participants_removed', $updateLog->changes);
        $updateDetail = app(CompanyChatGroupActivityLogFormatter::class)->format($updateLog);
        $this->assertStringContainsString('Participantes añadidos: Miembro nuevo', $updateDetail);
        $this->assertStringContainsString('Participantes eliminados: Segundo miembro', $updateDetail);
        $this->assertStringNotContainsString('participants', $updateDetail);
        $this->assertStringNotContainsString('success', $updateDetail);

        $this->actingAs($admin)
            ->get(ChatGroupResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Logs de grupos')
            ->assertSee('Grupo auditable editado')
            ->assertSee('Participantes añadidos');

        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)
            ->get(ChatGroupResource::getUrl('logs'))
            ->assertForbidden();

        $service->delete($group, $admin);

        $this->assertSame(3, CompanyChatGroupActivityLog::query()->count());
        $this->assertDatabaseHas('company_chat_group_activity_logs', [
            'action' => CompanyChatGroupActivityLog::ACTION_DELETED,
            'target_name' => 'Grupo auditable editado',
        ]);

        $deletedLog = CompanyChatGroupActivityLog::query()->where('action', CompanyChatGroupActivityLog::ACTION_DELETED)->sole();
        $this->assertStringContainsString('Nombre: Grupo auditable editado → Vacío', app(CompanyChatGroupActivityLogFormatter::class)->format($deletedLog));

        $historicalLog = new CompanyChatGroupActivityLog([
            'action' => CompanyChatGroupActivityLog::ACTION_UPDATED,
            'changes' => [
                'name' => ['from' => 'Grupo antiguo', 'to' => 'Grupo nuevo'],
                'participants' => ['from' => 'Segundo miembro, Miembro nuevo', 'to' => 'Miembro nuevo, Primer miembro'],
            ],
        ]);
        $historicalDetail = app(CompanyChatGroupActivityLogFormatter::class)->format($historicalLog);
        $this->assertStringContainsString('Nombre: Grupo antiguo → Grupo nuevo', $historicalDetail);
        $this->assertStringContainsString('Participantes añadidos: Primer miembro', $historicalDetail);
        $this->assertStringContainsString('Participantes eliminados: Segundo miembro', $historicalDetail);
    }

    public function test_management_service_creates_updates_and_deletes_without_deleting_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $first = User::factory()->create(['name' => 'Ana', 'is_active' => true]);
        $second = User::factory()->create(['name' => 'Bruno', 'is_active' => true]);
        $third = User::factory()->create(['name' => 'Carla', 'is_active' => true]);
        $service = app(CompanyChatGroupManagementService::class);

        $group = $service->create('Equipo soporte', [$first->id, $second->id, $second->id], $admin);
        $service->update($group, 'Equipo soporte ampliado', [$first->id, $third->id, $third->id], $admin);

        $this->assertSame(2, $group->fresh()->participants()->count());
        $this->assertDatabaseHas('company_chat_group_user', ['company_chat_group_id' => $group->id, 'user_id' => $first->id]);
        $this->assertDatabaseHas('company_chat_group_user', ['company_chat_group_id' => $group->id, 'user_id' => $third->id]);
        $this->assertDatabaseMissing('company_chat_group_user', ['company_chat_group_id' => $group->id, 'user_id' => $second->id]);
        $this->assertSame(2, CompanyChatGroup::query()->findOrFail($group->id)->participants()->count());
        $this->assertDatabaseHas('users', ['id' => $first->id]);
        $this->assertDatabaseHas('users', ['id' => $second->id]);
        $this->assertDatabaseHas('users', ['id' => $third->id]);

        $conversation = CompanyChatConversation::query()->where('company_chat_group_id', $group->id)->firstOrFail();
        $this->assertSame(2, CompanyChatMessage::query()->where('company_chat_conversation_id', $conversation->id)->count());

        $service->delete($group, $admin);
        $this->assertDatabaseMissing('company_chat_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('users', ['id' => $first->id]);
        $this->assertDatabaseHas('users', ['id' => $second->id]);
        $this->assertDatabaseHas('users', ['id' => $third->id]);
    }
}
