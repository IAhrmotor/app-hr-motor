<?php

namespace Tests\Feature;

use App\Models\AdminPermissionGrant;
use App\Models\Dealership;
use App\Models\ItTicket;
use App\Models\TicketTool;
use App\Models\User;
use App\Mail\ItTicketAssignedMail;
use App\Notifications\ItTicketAssignedNotification;
use App\Notifications\ItTicketMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
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
        Mail::fake();

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
            ->assertSee(route('tickets.reports'), false)
            ->assertSee($otherTicket->number, false)
            ->assertSee($ownTicket->number, false)
            ->assertSee('Vista previa', false)
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

        Mail::assertSent(ItTicketAssignedMail::class, function (ItTicketAssignedMail $mail) use ($assignableUser, $manager, $otherTicket): bool {
            $this->assertTrue($mail->envelope()->hasSubject('Te han asignado el ticket ' . $otherTicket->number . ' (Alta - Web HR Motor)'));
            $rendered = $mail->render();
            $this->assertStringContainsString('Ticket IT asignado', $rendered);
            $this->assertStringContainsString($otherTicket->title, $rendered);
            $this->assertStringContainsString('Alta', $rendered);
            $this->assertStringContainsString('Web HR Motor', $rendered);

            return $mail->hasTo($assignableUser->email)
                && $mail->assigneeName === $assignableUser->name
                && $mail->actorName === $manager->name
                && $mail->ticketNumber === $otherTicket->number
                && $mail->ticketTitle === $otherTicket->title
                && $mail->priorityLabel === 'Alta'
                && $mail->ticketTool === 'Web HR Motor';
        });

        $this->assertDatabaseHas('it_tickets', [
            'id' => $otherTicket->id,
            'assigned_to_user_id' => $assignableUser->id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    public function test_ticket_reports_page_is_only_available_to_users_who_can_manage_tickets(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Gestor Informes',
            'email' => 'gestor-informes@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'tickets-it.manage',
            'user_id' => $manager->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('tickets.reports'))
            ->assertOk()
            ->assertSee('Informes de ticketing', false)
            ->assertSee(route('tickets.index'), false);

        $regularUser = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'comercial-informes@example.com',
        ]);

        $this->actingAs($regularUser)
            ->get(route('tickets.reports'))
            ->assertForbidden();
    }

    public function test_ticket_reports_page_shows_open_incidents_by_it_person_and_status(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Gestor Informes',
            'email' => 'gestor-informes@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'tickets-it.manage',
            'user_id' => $manager->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $itOne = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'IT Uno',
            'email' => 'it-uno@example.com',
        ]);

        $itTwo = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'IT Dos',
            'email' => 'it-dos@example.com',
        ]);

        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        ItTicket::query()->create([
            'user_id' => $manager->id,
            'assigned_to_user_id' => $itOne->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-900101',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'new',
            'title' => 'Abierto 1',
            'description' => 'Abierto 1',
            'screenshots' => [],
        ]);

        ItTicket::query()->create([
            'user_id' => $manager->id,
            'assigned_to_user_id' => $itOne->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-900102',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'pending_user',
            'title' => 'Abierto 2',
            'description' => 'Abierto 2',
            'screenshots' => [],
        ]);

        ItTicket::query()->create([
            'user_id' => $manager->id,
            'assigned_to_user_id' => $itOne->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-900103',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'closed',
            'title' => 'Cerrado',
            'description' => 'Cerrado',
            'screenshots' => [],
        ]);

        ItTicket::query()->create([
            'user_id' => $manager->id,
            'assigned_to_user_id' => $itTwo->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-900201',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'reopen_requested',
            'title' => 'Reapertura',
            'description' => 'Reapertura',
            'screenshots' => [],
        ]);

        $this->actingAs($manager)
            ->get(route('tickets.reports'))
            ->assertOk()
            ->assertSee('Informes de ticketing', false)
            ->assertSee('IT Uno', false)
            ->assertSee('2 incidencias abiertas', false)
            ->assertSee('IT Dos', false)
            ->assertSee('1 incidencias abiertas', false)
            ->assertSee('Nuevo', false)
            ->assertSee('En curso', false)
            ->assertSee('Pendiente usuario', false)
            ->assertSee('Reapertura', false)
            ->assertDontSee('Cerrado', false)
            ->assertDontSee('Clausurado', false);
    }

    public function test_user_with_manage_permission_can_delete_tickets_and_cleanup_files(): void
    {
        Storage::fake('public');

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

        $requester = User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Solicitante',
            'email' => 'solicitante@example.com',
        ]);

        $ticketScreenshotPath = 'it-tickets/screenshots/ticket-shot.png';
        Storage::disk('public')->put($ticketScreenshotPath, 'ticket screenshot');

        $ticket = ItTicket::query()->create([
            'user_id' => $requester->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-333333',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'new',
            'title' => 'Ticket a eliminar',
            'description' => 'Este ticket se debe borrar.',
            'screenshots' => [
                [
                    'name' => 'ticket-shot.png',
                    'path' => $ticketScreenshotPath,
                ],
            ],
        ]);

        $messageAttachment = UploadedFile::fake()->image('mensaje.png', 120, 120);

        $this->actingAs($manager)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Mensaje con adjunto',
                'attachments' => [$messageAttachment],
            ])
            ->assertRedirect();

        $message = $ticket->fresh(['messages'])->messages->firstOrFail();
        $messageAttachmentPath = (string) data_get($message->attachments[0] ?? [], 'path', '');

        $this->assertNotSame('', $messageAttachmentPath);
        Storage::disk('public')->assertExists($ticketScreenshotPath);
        Storage::disk('public')->assertExists($messageAttachmentPath);

        $this->actingAs($manager)
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));

        $this->assertDatabaseMissing('it_tickets', [
            'id' => $ticket->id,
        ]);
        $this->assertDatabaseMissing('it_ticket_messages', [
            'it_ticket_id' => $ticket->id,
        ]);
        Storage::disk('public')->assertMissing($ticketScreenshotPath);
        Storage::disk('public')->assertMissing($messageAttachmentPath);
    }

    public function test_regular_user_cannot_open_the_tickets_interior(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'email' => 'no-access@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertForbidden();
    }

    public function test_ticket_creator_can_open_the_ticket_detail_interior(): void
    {
        $dealership = Dealership::query()->create([
            'name' => 'Delegación Norte',
        ]);

        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador',
            'email' => 'creador@example.com',
            'dealership_id' => $dealership->id,
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
            ->assertSee('Aqui se ve todo lo que ha escrito.', false)
            ->assertSee('Delegación', false)
            ->assertSee('Delegación Norte', false);
    }

    public function test_ticket_detail_shows_requester_extra_role_next_to_the_dealership(): void
    {
        $dealership = Dealership::query()->create([
            'name' => 'Delegación Sur',
        ]);

        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador Perfil',
            'email' => 'creador-perfil@example.com',
            'dealership_id' => $dealership->id,
        ]);

        $ticket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-333334',
            'tool' => $tool->name,
            'priority' => 'medium',
            'status' => 'new',
            'title' => 'Ticket con perfil',
            'description' => 'El solicitante tiene rol extra.',
            'screenshots' => [],
        ]);

        $this->actingAs($creator)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Delegación Sur', false)
            ->assertSee('Comercial', false);
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

    public function test_tickets_index_uses_open_statuses_by_default_when_paginating(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Gestor Filtro',
            'email' => 'gestor-filtro@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'tickets-it.manage',
            'user_id' => $manager->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $requester = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Solicitante Filtro',
            'email' => 'solicitante-filtro@example.com',
        ]);

        foreach (range(1, 12) as $index) {
            $ticket = ItTicket::query()->create([
                'user_id' => $requester->id,
                'assigned_to_user_id' => $manager->id,
                'ticket_tool_id' => $tool->id,
                'number' => sprintf('IT-100%03d', $index),
                'tool' => $tool->name,
                'priority' => 'medium',
                'status' => 'new',
                'title' => 'Ticket abierto ' . $index,
                'description' => 'Ticket abierto de prueba ' . $index,
                'screenshots' => [],
            ]);

            $ticket->forceFill([
                'created_at' => now()->subMinutes(12 - $index),
                'updated_at' => now()->subMinutes(12 - $index),
            ])->saveQuietly();
        }

        foreach (['closed', 'clausurado', 'closed'] as $offset => $status) {
            $ticket = ItTicket::query()->create([
                'user_id' => $requester->id,
                'assigned_to_user_id' => $manager->id,
                'ticket_tool_id' => $tool->id,
                'number' => sprintf('IT-200%03d', $offset + 1),
                'tool' => $tool->name,
                'priority' => 'medium',
                'status' => $status,
                'title' => 'Ticket cerrado ' . ($offset + 1),
                'description' => 'Ticket cerrado de prueba ' . ($offset + 1),
                'screenshots' => [],
            ]);

            $ticket->forceFill([
                'created_at' => now()->subHours($offset + 1),
                'updated_at' => now()->subHours($offset + 1),
            ])->saveQuietly();
        }

        $response = $this->actingAs($manager)
            ->get(route('tickets.index', [
                'managed_page' => 2,
                'assigned_page' => 2,
                'ajax' => 1,
            ]));

        $response->assertOk();

        $html = (string) $response->json('html');

        $this->assertStringContainsString('IT-100001', $html);
        $this->assertStringContainsString('IT-100002', $html);
        $this->assertStringNotContainsString('IT-200001', $html);
        $this->assertStringNotContainsString('IT-200002', $html);
        $this->assertStringNotContainsString('IT-200003', $html);
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

    public function test_assigned_tickets_can_be_sorted_by_last_update_while_combining_filters(): void
    {
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $assignedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'name' => 'Técnico Orden',
            'email' => 'tecnico-orden@example.com',
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Creador Orden',
            'email' => 'creador-orden@example.com',
        ]);

        $olderTicket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'assigned_to_user_id' => $assignedUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-900001',
            'tool' => $tool->name,
            'priority' => 'high',
            'status' => 'in_progress',
            'title' => 'Ticket más antiguo',
            'description' => 'Debe quedar antes en orden ascendente.',
            'screenshots' => [],
        ]);

        $newerTicket = ItTicket::query()->create([
            'user_id' => $creator->id,
            'assigned_to_user_id' => $assignedUser->id,
            'ticket_tool_id' => $tool->id,
            'number' => 'IT-900002',
            'tool' => $tool->name,
            'priority' => 'high',
            'status' => 'in_progress',
            'title' => 'Ticket más reciente',
            'description' => 'Debe quedar después en orden ascendente.',
            'screenshots' => [],
        ]);

        $olderTicket->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $newerTicket->forceFill([
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(6),
        ])->saveQuietly();

        $response = $this->actingAs($assignedUser)
            ->get(route('tickets.index', [
                'assigned_status' => 'in_progress',
                'assigned_priority' => 'high',
                'assigned_sort' => 'updated_asc',
                'ajax' => 1,
            ]));

        $response->assertOk();

        $html = (string) $response->json('html');
        $olderPosition = strpos($html, $olderTicket->number);
        $newerPosition = strpos($html, $newerTicket->number);

        $this->assertNotFalse($olderPosition);
        $this->assertNotFalse($newerPosition);
        $this->assertTrue($olderPosition < $newerPosition);
    }
}
