<?php

namespace Tests\Feature;

use App\Models\PolicyAcceptance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PolicyAcceptanceLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_policy_acceptance_logs_page_lists_activity_and_allows_csv_download(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $employee = User::factory()->create([
            'name' => 'Empleado Chat',
            'email' => 'empleado@example.com',
        ]);

        PolicyAcceptance::query()->create([
            'user_id' => $employee->id,
            'user_email' => $employee->email,
            'policy_version' => '2026-05-28-v1',
            'accepted_at' => now(),
            'ip_address' => '10.0.0.25',
            'user_agent' => 'Mozilla/5.0 Test',
            'source' => 'web-chat',
        ]);

        $pageResponse = $this->actingAs($admin)->get(route('admin.policy-acceptance-logs.index'));

        $pageResponse
            ->assertOk()
            ->assertSee('Política de aceptación')
            ->assertSee('Empleado Chat')
            ->assertSee('empleado@example.com')
            ->assertSee(route('admin.policy-acceptance-logs.export'), false);

        $downloadResponse = $this->actingAs($admin)->get(route('admin.policy-acceptance-logs.export'));

        $downloadResponse
            ->assertOk()
            ->assertDownload();

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('fecha_hora;usuario;email_usuario;version_politica;ip;user_agent;source', $content);
        $this->assertStringContainsString('Empleado Chat', $content);
        $this->assertStringContainsString('empleado@example.com', $content);
        $this->assertStringContainsString('2026-05-28-v1', $content);
        $this->assertStringContainsString('web-chat', $content);
    }

    public function test_admin_policy_acceptance_logs_can_be_filtered_by_date_range_and_user(): void
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

        PolicyAcceptance::query()->create([
            'user_id' => $employee->id,
            'user_email' => $employee->email,
            'policy_version' => '2026-05-28-v1',
            'accepted_at' => Carbon::parse('2026-05-28 10:00:00'),
            'ip_address' => '10.0.0.10',
            'user_agent' => 'UA 1',
            'source' => 'web-chat',
        ]);

        PolicyAcceptance::query()->create([
            'user_id' => $otherUser->id,
            'user_email' => $otherUser->email,
            'policy_version' => '2026-05-28-v1',
            'accepted_at' => Carbon::parse('2026-05-27 10:00:00'),
            'ip_address' => '10.0.0.11',
            'user_agent' => 'UA 2',
            'source' => 'web-chat',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.policy-acceptance-logs.index', [
            'date_from' => '2026-05-28',
            'date_to' => '2026-05-28',
            'user' => $employee->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Empleado Filtro')
            ->assertDontSee('otro@example.com')
            ->assertSee('Del 28/05/2026 al 28/05/2026')
            ->assertSee('filtro@example.com');

        $downloadResponse = $this->actingAs($admin)->get(route('admin.policy-acceptance-logs.export', [
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
