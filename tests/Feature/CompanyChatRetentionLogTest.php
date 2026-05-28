<?php

namespace Tests\Feature;

use App\Models\CompanyChatRetentionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CompanyChatRetentionLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_index_shows_the_chat_retention_log_shortcut(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response
            ->assertOk()
            ->assertSee('Borrado chats')
            ->assertSee(route('admin.chat-retention-logs.index'), false);
    }

    public function test_admin_chat_retention_logs_page_lists_activity_and_allows_csv_download(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $employee = User::factory()->create([
            'name' => 'Empleado Borrado',
            'email' => 'empleado@example.com',
        ]);

        CompanyChatRetentionLog::query()->create([
            'executed_at' => Carbon::parse('2026-05-28 05:30:00'),
            'cutoff' => Carbon::parse('2025-11-28 05:30:00'),
            'status' => 'success',
            'deleted_count' => 4,
            'affected_user_ids' => [$employee->id],
            'affected_users' => [sprintf('%s (%d)', $employee->name, 4)],
            'error_count' => 0,
            'error_summary' => null,
            'errors' => [],
            'source' => 'cron',
        ]);

        $pageResponse = $this->actingAs($admin)->get(route('admin.chat-retention-logs.index'));

        $pageResponse
            ->assertOk()
            ->assertSee('Borrado chats')
            ->assertSee('Empleado Borrado')
            ->assertSee('4')
            ->assertSee(route('admin.chat-retention-logs.export'), false);

        $downloadResponse = $this->actingAs($admin)->get(route('admin.chat-retention-logs.export'));

        $downloadResponse
            ->assertOk()
            ->assertDownload();

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('fecha_hora;estado;mensajes_eliminados;usuarios_afectados;errores;origen;corte', $content);
        $this->assertStringContainsString('Empleado Borrado', $content);
        $this->assertStringContainsString('Correcto', $content);
        $this->assertStringContainsString('cron', $content);
    }

    public function test_admin_chat_retention_logs_can_be_filtered_by_date_range_and_user(): void
    {
        Carbon::setTestNow('2026-05-28 10:00:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Otro Usuario',
            'email' => 'otro@example.com',
        ]);
        $employee = User::factory()->create([
            'name' => 'Empleado Filtro',
            'email' => 'filtro@example.com',
        ]);

        CompanyChatRetentionLog::query()->create([
            'executed_at' => Carbon::parse('2026-05-28 05:30:00'),
            'cutoff' => Carbon::parse('2025-11-28 05:30:00'),
            'status' => 'success',
            'deleted_count' => 2,
            'affected_user_ids' => [$employee->id],
            'affected_users' => [sprintf('%s (%d)', $employee->name, 2)],
            'error_count' => 0,
            'error_summary' => null,
            'errors' => [],
            'source' => 'cron',
        ]);

        CompanyChatRetentionLog::query()->create([
            'executed_at' => Carbon::parse('2026-05-27 05:30:00'),
            'cutoff' => Carbon::parse('2025-11-27 05:30:00'),
            'status' => 'failed',
            'deleted_count' => 1,
            'affected_user_ids' => [$otherUser->id],
            'affected_users' => [sprintf('%s (%d)', $otherUser->name, 1)],
            'error_count' => 1,
            'error_summary' => 'Error de prueba',
            'errors' => [
                [
                    'message_id' => 99,
                    'conversation_id' => 3,
                    'sender_id' => $otherUser->id,
                    'sender_name' => $otherUser->name,
                    'error' => 'Error de prueba',
                ],
            ],
            'source' => 'cron',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.chat-retention-logs.index', [
            'date_from' => '2026-05-28',
            'date_to' => '2026-05-28',
            'user' => $employee->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Empleado Filtro')
            ->assertSee('Del 28/05/2026 al 28/05/2026')
            ->assertSee('2')
            ->assertDontSee('Error de prueba')
            ->assertDontSee('Con errores');

        $downloadResponse = $this->actingAs($admin)->get(route('admin.chat-retention-logs.export', [
            'date_from' => '2026-05-28',
            'date_to' => '2026-05-28',
            'user' => $employee->id,
        ]));

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('Empleado Filtro', $content);
        $this->assertStringNotContainsString('Otro Usuario', $content);

        Carbon::setTestNow();
    }
}
