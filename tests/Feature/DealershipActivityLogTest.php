<?php

namespace Tests\Feature;

use App\Filament\Resources\Dealerships\DealershipResource;
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

    public function test_filament_dealership_logs_page_lists_activity_and_is_admin_only(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $manager = User::factory()->create([
            'role' => 'gestor',
            'name' => 'Gestor Principal',
            'email' => 'gestor@example.com',
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

        $this->actingAs($admin)
            ->get(DealershipResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Logs de delegaciones')
            ->assertSee('Valencia Centro')
            ->assertSee('Admin Principal');

        $this->actingAs($manager)
            ->get(DealershipResource::getUrl('logs'))
            ->assertForbidden();
    }

    public function test_filament_dealership_logs_csv_download_works(): void
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

        $downloadResponse = $this->actingAs($admin)->get(route('backoffice.dealership-logs.export'));

        $downloadResponse
            ->assertOk()
            ->assertDownload();

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('fecha_hora;accion;gestionado_por', $content);
        $this->assertStringContainsString('Valencia Centro', $content);
        $this->assertStringContainsString('Admin Principal', $content);
        $this->assertStringContainsString('Telefono: 961000111 -> 961000333', $content);
    }

    public function test_old_admin_dealership_logs_urls_are_removed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/logs/delegaciones')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/logs/delegaciones/descargar')
            ->assertNotFound();
    }
}
