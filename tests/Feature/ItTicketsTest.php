<?php

namespace Tests\Feature;

use App\Mail\ItTicketCreatedMail;
use App\Models\ItTicket;
use App\Models\TicketTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItTicketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_open_the_it_ticket_interior(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Usuario Prueba',
        ]);

        $this->actingAs($user)
            ->get(route('it-tickets.index'))
            ->assertOk()
            ->assertSee('Tus tickets de IT, en un solo sitio', false)
            ->assertSee('Crear incidencia', false)
            ->assertSee(route('it-tickets.create'), false);
    }

    public function test_user_can_open_the_create_ticket_interior(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Usuario Prueba',
        ]);
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $this->actingAs($user)
            ->get(route('it-tickets.create'))
            ->assertOk()
            ->assertSee('Crear incidencia', false)
            ->assertSee('Tipo de incidencia', false)
            ->assertSee('Prioridad', false)
            ->assertSee($tool->name, false);
    }

    public function test_user_can_create_a_ticket_and_persist_it_in_the_database(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Usuario Prueba',
        ]);
        $tool = TicketTool::query()->create([
            'name' => 'Web HR Motor',
            'color' => '#1d4ed8',
        ]);

        $response = $this->actingAs($user)
            ->post(route('it-tickets.store'), [
                'tool' => (string) $tool->id,
                'priority' => 'urgent',
                'title' => 'El formulario no guarda',
                'description' => 'Al enviar el formulario aparece un error en pantalla.',
                'screenshots' => [
                    UploadedFile::fake()->image('captura-1.png'),
                ],
            ]);

        $response->assertRedirect(route('it-tickets.index'));

        $ticket = ItTicket::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->assertSame('El formulario no guarda', $ticket->title);
        $this->assertSame($tool->name, $ticket->tool);
        $this->assertSame($tool->id, $ticket->ticket_tool_id);
        $this->assertSame('urgent', $ticket->priority);
        $this->assertSame('new', $ticket->status);
        $this->assertCount(1, $ticket->screenshots ?? []);
        Storage::disk('public')->assertExists($ticket->screenshots[0]['path']);
    }

    public function test_user_can_create_a_ticket_and_notify_it_department_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
        ]);
        $tool = TicketTool::query()->create([
            'name' => 'Salesforce',
            'color' => '#1d4ed8',
        ]);

        $response = $this->actingAs($user)
            ->post(route('it-tickets.store'), [
                'tool' => (string) $tool->id,
                'priority' => 'high',
                'title' => 'Problema de acceso',
                'description' => 'No me deja entrar.',
                'screenshots' => [],
            ]);

        $response->assertRedirect(route('it-tickets.index'));

        $ticket = ItTicket::query()->latest('id')->firstOrFail();

        Mail::assertSent(ItTicketCreatedMail::class, function (ItTicketCreatedMail $mail) use ($user, $ticket): bool {
            $this->assertSame($user->name, $mail->reporterName);
            $this->assertSame('Alta', $mail->priorityLabel);
            $this->assertSame('Problema de acceso', $mail->ticketTitle);
            $this->assertTrue($mail->envelope()->hasSubject('Juan Pérez ha abierto un ticket Alta con asunto Problema de acceso.'));

            $rendered = $mail->render();
            $this->assertStringContainsString('Nueva incidencia IT', $rendered);
            $this->assertStringContainsString('Problema de acceso', $rendered);
            $this->assertStringContainsString('Salesforce', $rendered);

            return $mail->hasTo('carlos.torres@hrmotor.es')
                && $mail->hasCc('javier.arruabarrena@hrmotor.com');
        });
    }
}
