<?php

namespace Tests\Feature\Admin;

use App\Models\Dealership;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_create_a_zone_and_assign_dealerships(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $firstDealership = Dealership::factory()->create(['name' => 'Bilbao']);
        $secondDealership = Dealership::factory()->create(['name' => 'Valladolid']);

        $response = $this->actingAs($admin)->post(route('admin.zones.store'), [
            'name' => 'Zona Norte',
            'dealership_ids' => [$firstDealership->id, $secondDealership->id],
        ]);

        $response->assertRedirect(route('admin.zones.index'));

        $zone = Zone::query()->where('name', 'Zona Norte')->firstOrFail();

        $this->assertSame($zone->id, $firstDealership->refresh()->zone_id);
        $this->assertSame($zone->id, $secondDealership->refresh()->zone_id);

        $log = ZoneActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(ZoneActivityLog::ACTION_CREATED, $log->action);
        $this->assertSame('Zona Norte', $log->target_name);
        $this->assertSame(['Bilbao', 'Valladolid'], $log->target_dealerships);
    }

    public function test_a_dealership_cannot_be_assigned_to_two_zones(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $existingZone = Zone::query()->create(['name' => 'Zona Este']);
        $dealership = Dealership::factory()->create(['name' => 'Zaragoza', 'zone_id' => $existingZone->id]);

        $response = $this->actingAs($admin)->post(route('admin.zones.store'), [
            'name' => 'Zona Oeste',
            'dealership_ids' => [$dealership->id],
        ]);

        $response->assertSessionHasErrors('dealership_ids.0');

        $this->assertDatabaseMissing('zones', [
            'name' => 'Zona Oeste',
        ]);
    }

    public function test_old_admin_zone_logs_routes_are_removed(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/logs/zonas')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/logs/zonas/descargar')
            ->assertNotFound();
    }

    public function test_manager_can_access_zone_management_without_old_zone_logs_route(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'name' => 'Gestor Principal',
            'email' => 'gestor@example.com',
        ]);

        $dealership = Dealership::factory()->create(['name' => 'Alicante']);

        $this->actingAs($manager)
            ->get(route('admin.zones.index'))
            ->assertOk()
            ->assertSee('Gestión de zonas');

        $this->actingAs($manager)
            ->post(route('admin.zones.store'), [
                'name' => 'Zona Levante',
                'dealership_ids' => [$dealership->id],
            ])
            ->assertRedirect(route('admin.zones.index'));

        $this->actingAs($manager)
            ->get('/admin/logs/zonas')
            ->assertNotFound();
    }
}
