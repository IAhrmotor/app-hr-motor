<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\DealershipActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DealershipActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dealership_creation_is_logged_with_actor_and_target_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)->post(route('dealerships.store'), [
            'name' => 'Sevilla Este',
            'salesforce_id' => 'DLR-SEV-001',
            'phone' => '954000111',
            'google_maps_url' => 'https://maps.google.com/?q=sevilla+este',
            'reviews_url' => 'https://example.com/resenas/sevilla',
            'image' => UploadedFile::fake()->image('sevilla.png', 400, 400),
        ])->assertRedirect(route('dealerships.index'));

        $this->assertDatabaseHas('dealership_activity_logs', [
            'action' => DealershipActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => 'Admin Principal',
            'target_name' => 'Sevilla Este',
            'target_salesforce_id' => 'DLR-SEV-001',
            'target_phone' => '954000111',
        ]);
    }

    public function test_dealership_update_is_logged_with_changed_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Bilbao Centro',
            'salesforce_id' => 'DLR-BIL-001',
            'phone' => '944000111',
            'google_maps_url' => 'https://maps.google.com/?q=bilbao+centro',
            'reviews_url' => 'https://example.com/resenas/bilbao-centro',
            'image_path' => 'images/dealerships/bilbao-centro.png',
        ]);

        $this->actingAs($admin)->put(route('dealerships.update', $dealership), [
            'name' => 'Bilbao Norte',
            'salesforce_id' => 'DLR-BIL-002',
            'phone' => '944000222',
            'google_maps_url' => 'https://maps.google.com/?q=bilbao+norte',
            'reviews_url' => 'https://example.com/resenas/bilbao-norte',
        ])->assertRedirect(route('dealerships.index'));

        $log = DealershipActivityLog::query()
            ->where('action', DealershipActivityLog::ACTION_UPDATED)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Bilbao Norte', $log->target_name);
        $this->assertSame('Bilbao Centro', $log->changes['Nombre']['from']);
        $this->assertSame('Bilbao Norte', $log->changes['Nombre']['to']);
        $this->assertSame('DLR-BIL-001', $log->changes['ID Salesforce']['from']);
        $this->assertSame('DLR-BIL-002', $log->changes['ID Salesforce']['to']);
    }

    public function test_dealership_deletion_is_logged_before_removing_the_dealership(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Murcia Centro',
        ]);

        $this->actingAs($admin)->delete(route('dealerships.destroy', $dealership))
            ->assertRedirect(route('dealerships.index'));

        $this->assertDatabaseMissing('dealerships', [
            'id' => $dealership->id,
        ]);

        $this->assertDatabaseHas('dealership_activity_logs', [
            'action' => DealershipActivityLog::ACTION_DELETED,
            'actor_user_id' => $admin->id,
            'target_name' => 'Murcia Centro',
        ]);
    }

    public function test_admin_dealership_logs_page_lists_activity_and_allows_csv_download(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        DealershipActivityLog::query()->create([
            'action' => DealershipActivityLog::ACTION_UPDATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_dealership_id' => null,
            'target_name' => 'Valencia Centro',
            'target_salesforce_id' => 'DLR-VAL-001',
            'target_phone' => '961000333',
            'changes' => [
                'Telefono' => [
                    'from' => '961000111',
                    'to' => '961000333',
                ],
            ],
            'created_at' => now(),
        ]);

        $pageResponse = $this->actingAs($admin)->get(route('admin.dealership-logs.index'));

        $pageResponse
            ->assertOk()
            ->assertSee('Logs de delegaciones')
            ->assertSee('Valencia Centro')
            ->assertSee('Admin Principal')
            ->assertSee(route('admin.dealership-logs.export'), false);

        $downloadResponse = $this->actingAs($admin)->get(route('admin.dealership-logs.export'));

        $downloadResponse
            ->assertOk()
            ->assertDownload();

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('fecha_hora;accion;gestionado_por', $content);
        $this->assertStringContainsString('Valencia Centro', $content);
        $this->assertStringContainsString('Admin Principal', $content);
        $this->assertStringContainsString('Telefono: ""961000111"" -> ""961000333""', $content);
    }

    public function test_admin_dealership_logs_can_be_filtered_by_date_range_and_actor(): void
    {
        Carbon::setTestNow('2026-03-23 10:00:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $manager = User::factory()->create([
            'role' => 'gestor',
            'name' => 'Gestor Norte',
            'email' => 'gestor@example.com',
        ]);

        DealershipActivityLog::query()->create([
            'action' => DealershipActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_dealership_id' => null,
            'target_name' => 'Delegacion Hoy Admin',
            'target_salesforce_id' => 'DLR-ADM-001',
            'target_phone' => '910000111',
            'changes' => null,
            'created_at' => Carbon::parse('2026-03-23 10:00:00'),
        ]);

        DealershipActivityLog::query()->create([
            'action' => DealershipActivityLog::ACTION_CREATED,
            'actor_user_id' => $manager->id,
            'actor_name' => $manager->name,
            'actor_email' => $manager->email,
            'target_dealership_id' => null,
            'target_name' => 'Delegacion Hoy Gestor',
            'target_salesforce_id' => 'DLR-GES-001',
            'target_phone' => '910000222',
            'changes' => null,
            'created_at' => Carbon::parse('2026-03-23 11:00:00'),
        ]);

        DealershipActivityLog::query()->create([
            'action' => DealershipActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_dealership_id' => null,
            'target_name' => 'Delegacion Ayer Admin',
            'target_salesforce_id' => 'DLR-ADM-002',
            'target_phone' => '910000333',
            'changes' => null,
            'created_at' => Carbon::parse('2026-03-22 10:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dealership-logs.index', [
            'date_from' => '2026-03-23',
            'date_to' => '2026-03-23',
            'actor' => $manager->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Delegacion Hoy Gestor')
            ->assertDontSee('Delegacion Hoy Admin')
            ->assertDontSee('Delegacion Ayer Admin')
            ->assertSee('Del 23/03/2026 al 23/03/2026')
            ->assertSee('Gestor Norte');

        $downloadResponse = $this->actingAs($admin)->get(route('admin.dealership-logs.export', [
            'date_from' => '2026-03-23',
            'date_to' => '2026-03-23',
            'actor' => $manager->id,
        ]));

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('Delegacion Hoy Gestor', $content);
        $this->assertStringNotContainsString('Delegacion Hoy Admin', $content);
        $this->assertStringNotContainsString('Delegacion Ayer Admin', $content);

        Carbon::setTestNow();
    }
}
