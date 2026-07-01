<?php

namespace Tests\Feature;

use App\Models\AdminPermissionGrant;
use App\Models\ItTicket;
use App\Models\TicketTool;
use App\Models\User;
use App\Notifications\ItTicketAssignedNotification;
use App\Notifications\ItTicketMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketsInteriorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_it_user_without_management_permission_sees_only_assigned_tickets(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $assignedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Técnico Asignado',
            'email' => 'tecnico.asignado@example.com',
        ]);

        $otherUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Otro Técnico',
            'email' => 'otro.tecnico@example.com',
        ]);

        $visibleTicket = ItTicket::query()->create([
            'user_id' => $otherUser->id,
            'assigned_to_user_id' => $assignedUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-123456',
            'tool' => $tool->name,
            'priority' => 'high',
            'status' => 'in_progress',
            'title' => 'Se ve este ticket',
            'description' => 'Descripción visible para el técnico.',
            'screenshots' => [],
        ]);

        ItTicket::query()->create([
            'user_id' => $otherUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-654321',
            'tool' => $tool->name,
            'priority' => 'low',
            'status' => 'new',
            'title' => 'No se ve este ticket',
            'description' => 'No está asignado al técnico.',
            'screenshots' => [],
        ]);

        $this->actingAs($assignedUser)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Gestión de tickets', false)
            ->assertSee($visibleTicket->number, false)
            ->assertSee('Mis tickets', false)
            ->assertDontSee('IT-654321', false)
            ->assertDontSee('Todos los tickets', false);
    }

    public function test_user_with_manage_permission_sees_all_tickets_and_assigns_them(): void
    {
        Notification::fake();

        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Gestor IT',
            'email' => 'gestor.it@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'tickets-it.manage',
            'user_id' => $manager->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $assignableUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Técnico Asignable',
            'email' => 'tecnico.asignable@example.com',
        ]);

        $otherTicket = ItTicket::query()->create([
            'user_id' => $assignableUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-222222',
            'tool' => $tool->name,
            'priority' => 'urgent',
            'status' => 'new',
            'title' => 'Ticket a asignar',
            'description' => 'Está pendiente de reparto.',
            'screenshots' => [],
        ]);

        $ownTicket = ItTicket::query()->create([
            'user_id' => $manager->id,
            'assigned_to_user_id' => $manager->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-111111',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'in_progress',
            'title' => 'Ticket propio',
            'description' => 'Asignado al gestor.',
            'screenshots' => [],
        ]);

        $this->actingAs($manager)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Todos los tickets', false)
            ->assertSee($otherTicket->number, false)
            ->assertSee($ownTicket->number, false)
            ->assertSee(route('tickets.assign', $otherTicket), false);

        $this->actingAs($manager)
            ->post(route('tickets.assign', $otherTicket), [
                'priority' => 'high',
                'assigned_to_user_id' => $assignableUser->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo(
            $assignableUser,
            ItTicketAssignedNotification::class,
            function (ItTicketAssignedNotification $notification, array $channels, User $notifiable) use ($otherTicket, $manager): bool {
                $payload = $notification->toDatabase($notifiable);

                return $payload['ticket_id'] === $otherTicket->id
                    && $payload['ticket_number'] === $otherTicket->number
                    && $payload['actor_name'] === $manager->name
                    && $channels === ['database'];
            }
        );

        $this->assertDatabaseHas('it_tickets', [
            'id' => $otherTicket->id,
            'assigned_to_user_id' => $assignableUser->id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    public function test_regular_user_cannot_open_the_tickets_interior(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'no-access@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertForbidden();
    }

    public function test_ticket_creator_can_open_the_ticket_detail_interior(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador',
            'email' => 'creador@example.com',
        ]);

        $ticket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-333333',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'new',
            'title' => 'El usuario crea la incidencia',
            'description' => 'Aqui se ve todo lo que ha escrito.',
            'screenshots' => [],
        ]);

        $this->actingAs($creator)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('El usuario crea la incidencia', false)
            ->assertSee('Aqui se ve todo lo que ha escrito.', false);
    }

    public function test_ticket_participants_can_reply_with_images(): void
    {
        Storage::fake('public');
        Notification::fake();

        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador',
            'email' => 'creador-reply@example.com',
        ]);

        $itUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Técnico',
            'email' => 'tecnico-reply@example.com',
        ]);

        $ticket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'assigned_to_user_id' => $itUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-444444',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'in_progress',
            'title' => 'Ticket con conversación',
            'description' => 'Hay que poder responder al hilo.',
            'screenshots' => [],
        ]);

        $response = $this->actingAs($creator)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Te paso una captura.',
                'attachments' => [
                    UploadedFile::fake()->image('captura-ticket.png'),
                ],
            ]);

        $response->assertRedirect();

        $message = $ticket->fresh()->messages()->firstOrFail();

        $this->assertSame('Te paso una captura.', $message->body);
        $this->assertCount(1, $message->attachments ?? []);
        Storage::disk('public')->assertExists($message->attachments[0]['path']);
        Notification::assertSentTo(
            $itUser,
            ItTicketMessageNotification::class,
            function (ItTicketMessageNotification $notification, array $channels, User $notifiable) use ($ticket, $creator): bool {
                $payload = $notification->toDatabase($notifiable);

                return $channels === ['database']
                    && $payload['type'] === 'it-ticket.reply.received'
                    && $payload['ticket_id'] === $ticket->id
                    && $payload['actor_name'] === $creator->name;
            }
        );

        $response = $this->actingAs($itUser)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Lo reviso ahora.',
            ]);

        $response->assertRedirect();

        $ticket->refresh();

        $this->assertSame('pending_user', $ticket->status);
        $this->assertSame(2, $ticket->messages()->count());
        Notification::assertSentTo(
            $creator,
            ItTicketMessageNotification::class,
            function (ItTicketMessageNotification $notification, array $channels, User $notifiable) use ($ticket, $itUser): bool {
                $payload = $notification->toDatabase($notifiable);

                return $channels === ['database']
                    && $payload['type'] === 'it-ticket.reply.received'
                    && $payload['ticket_id'] === $ticket->id
                    && $payload['actor_name'] === $itUser->name;
            }
        );
    }

    public function test_it_assignee_can_close_ticket_and_block_future_replies(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador',
            'email' => 'creador-close@example.com',
        ]);

        $itUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Técnico Cierre',
            'email' => 'tecnico-close@example.com',
        ]);

        $ticket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'assigned_to_user_id' => $itUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-555555',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'in_progress',
            'title' => 'Ticket cerrable',
            'description' => 'Se puede cerrar desde el hilo.',
            'screenshots' => [],
        ]);

        $this->actingAs($itUser)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Queda resuelto, lo cierro.',
                'close_ticket' => '1',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $messages = $ticket->messages()->get();

        $this->assertSame('closed', $ticket->status);
        $this->assertSame(2, $messages->count());
        $this->assertSame('Queda resuelto, lo cierro.', $messages->first()->body);
        $this->assertSame('Ticket cerrado por Técnico Cierre.', $messages->last()->body);

        $this->actingAs($creator)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Intento responder después del cierre.',
            ])
            ->assertForbidden();
    }

    public function test_ticket_creator_can_close_ticket_with_comment(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador Cierre',
            'email' => 'creador-close-owner@example.com',
        ]);

        $ticket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-666666',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'in_progress',
            'title' => 'Ticket del creador',
            'description' => 'El creador también puede cerrarlo.',
            'screenshots' => [],
        ]);

        $this->actingAs($creator)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Lo he revisado y ya está resuelto.',
                'close_ticket' => '1',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $messages = $ticket->messages()->get();

        $this->assertSame('closed', $ticket->status);
        $this->assertSame(2, $messages->count());
        $this->assertSame('Lo he revisado y ya está resuelto.', $messages->first()->body);
        $this->assertSame('Ticket cerrado por Creador Cierre.', $messages->last()->body);
    }

    public function test_ticket_creator_reply_sets_status_back_to_in_progress(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador En Curso',
            'email' => 'creador-in-progress@example.com',
        ]);

        $itUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Técnico En Curso',
            'email' => 'tecnico-in-progress@example.com',
        ]);

        $ticket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'assigned_to_user_id' => $itUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-777777',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'pending_user',
            'title' => 'Ticket reabierto',
            'description' => 'El creador responde y debe volver a curso.',
            'screenshots' => [],
        ]);

        $this->actingAs($creator)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'He añadido la información que faltaba.',
            ])
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame('in_progress', $ticket->status);
        $this->assertSame(1, $ticket->messages()->count());
    }

    public function test_tickets_index_paginates_each_section_at_ten_items(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Gestor Paginado',
            'email' => 'gestor-paginado@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'tickets-it.manage',
            'user_id' => $manager->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador Paginado',
            'email' => 'creador-paginado@example.com',
        ]);

        foreach (range(1, 11) as $index) {
            $ticket = ItTicket::query()->create([
                'user_id' => $creator->id,
                'assigned_to_user_id' => $manager->id,
                'ticket_tool_id' => $tool->id,
                'number' => sprintf('IT-%06d', $index),
                'tool' => $tool->name,
                'priority' => 'medium',
                'status' => 'in_progress',
                'title' => 'Ticket ' . $index,
                'description' => 'Ticket de prueba ' . $index,
                'screenshots' => [],
            ]);

            $ticket->forceFill([
                'created_at' => now()->subMinutes(11 - $index),
                'updated_at' => now()->subMinutes(11 - $index),
            ])->saveQuietly();
        }

        $this->actingAs($manager)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('managed_page=2', false)
            ->assertSee('assigned_page=2', false)
            ->assertSee('IT-000011', false)
            ->assertDontSee('IT-000001', false);
    }

    public function test_assigned_tickets_search_works_beyond_first_page(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $assignedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Técnico Buscar',
            'email' => 'tecnico-buscar@example.com',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador Buscar',
            'email' => 'creador-buscar@example.com',
        ]);

        foreach (range(1, 11) as $index) {
            $ticket = ItTicket::query()->create([
                'user_id' => $creator->id,
                'assigned_to_user_id' => $assignedUser->id,
                'ticket_tool_id' => $tool->id,
                'number' => sprintf('IT-%06d', $index),
                'tool' => $tool->name,
                'priority' => 'medium',
                'status' => 'in_progress',
                'title' => 'Ticket de búsqueda ' . $index,
                'description' => 'Ticket de prueba ' . $index,
                'screenshots' => [],
            ]);

            $ticket->forceFill([
                'created_at' => now()->subMinutes(11 - $index),
                'updated_at' => now()->subMinutes(11 - $index),
            ])->saveQuietly();
        }

        $response = $this->actingAs($assignedUser)
            ->get(route('tickets.index', [
                'assigned_search' => 'IT-000001',
                'ajax' => 1,
            ]));

        $response->assertOk();

        $html = (string) $response->json('html');

        $this->assertStringContainsString('IT-000001', $html);
        $this->assertStringNotContainsString('IT-000011', $html);
    }
}
