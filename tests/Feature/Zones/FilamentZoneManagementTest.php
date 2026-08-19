<?php

namespace Tests\Feature\Zones;

use App\Filament\Resources\Zones\Pages\CreateZone;
use App\Filament\Resources\Zones\Pages\EditZone;
use App\Filament\Resources\Zones\ZoneResource;
use App\Models\Dealership;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentZoneManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_filament_can_create_zone_with_selected_dealerships(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $firstDealership = Dealership::factory()->create(['name' => 'Bilbao']);
        $secondDealership = Dealership::factory()->create(['name' => 'Valladolid']);

        Livewire::actingAs($admin);

        Livewire::test(CreateZone::class)
            ->set('data.name', 'Zona Norte')
            ->set('data.dealership_ids', [$firstDealership->id, $secondDealership->id])
            ->call('create')
            ->assertHasNoErrors();

        $zone = Zone::query()->where('name', 'Zona Norte')->firstOrFail();

        $this->assertSame($zone->id, $firstDealership->refresh()->zone_id);
        $this->assertSame($zone->id, $secondDealership->refresh()->zone_id);

        $log = ZoneActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(ZoneActivityLog::ACTION_CREATED, $log->action);
        $this->assertSame('Zona Norte', $log->target_name);
        $this->assertSame(['Bilbao', 'Valladolid'], $log->target_dealerships);
        $this->assertSame([
            'Nombre' => [
                'from' => null,
                'to' => 'Zona Norte',
            ],
            'Delegaciones' => [
                'from' => null,
                'to' => 'Bilbao, Valladolid',
            ],
        ], $log->changes);
    }

    public function test_filament_edit_zone_removes_unselected_dealerships(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $zone = Zone::query()->create(['name' => 'Zona Centro']);

        $firstDealership = Dealership::factory()->create([
            'name' => 'Madrid Centro',
            'zone_id' => $zone->id,
        ]);

        $secondDealership = Dealership::factory()->create([
            'name' => 'Madrid Sur',
            'zone_id' => $zone->id,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(EditZone::class, ['record' => $zone->getKey()])
            ->set('data.name', 'Zona Centro')
            ->set('data.dealership_ids', [$firstDealership->id])
            ->call('save', false, false)
            ->assertHasNoErrors();

        $this->assertSame($zone->id, $firstDealership->refresh()->zone_id);
        $this->assertNull($secondDealership->refresh()->zone_id);

        $log = ZoneActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(ZoneActivityLog::ACTION_UPDATED, $log->action);
        $this->assertSame('Zona Centro', $log->target_name);
        $this->assertSame(['Madrid Centro'], $log->target_dealerships);
        $this->assertSame([
            'Delegaciones' => [
                'from' => 'Madrid Centro, Madrid Sur',
                'to' => 'Madrid Centro',
            ],
        ], $log->changes);
    }

    public function test_filament_edit_zone_allows_clearing_all_dealerships(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $zone = Zone::query()->create(['name' => 'Zona Levante']);

        $dealership = Dealership::factory()->create([
            'name' => 'Alicante',
            'zone_id' => $zone->id,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(EditZone::class, ['record' => $zone->getKey()])
            ->set('data.name', 'Zona Levante')
            ->set('data.dealership_ids', [])
            ->call('save', false, false)
            ->assertHasNoErrors();

        $this->assertNull($dealership->refresh()->zone_id);
        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
            'name' => 'Zona Levante',
        ]);

        $log = ZoneActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(ZoneActivityLog::ACTION_UPDATED, $log->action);
        $this->assertSame([], $log->target_dealerships);
        $this->assertSame([
            'Delegaciones' => [
                'from' => 'Alicante',
                'to' => '',
            ],
        ], $log->changes);
    }

    public function test_filament_delete_zone_records_zone_log(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $zone = Zone::query()->create(['name' => 'Zona Sur']);

        $dealership = Dealership::factory()->create([
            'name' => 'Murcia',
            'zone_id' => $zone->id,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(EditZone::class, ['record' => $zone->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('zones', [
            'id' => $zone->id,
        ]);
        $this->assertNull($dealership->refresh()->zone_id);

        $log = ZoneActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(ZoneActivityLog::ACTION_DELETED, $log->action);
        $this->assertSame('Zona Sur', $log->target_name);
        $this->assertSame(['Murcia'], $log->target_dealerships);
        $this->assertSame([
            'Nombre' => [
                'from' => 'Zona Sur',
                'to' => null,
            ],
            'Delegaciones' => [
                'from' => 'Murcia',
                'to' => null,
            ],
        ], $log->changes);
    }

    public function test_admin_can_see_zone_logs_button_and_open_logs_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        ZoneActivityLog::query()->create([
            'action' => ZoneActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_zone_id' => Zone::query()->create(['name' => 'Zona Prueba'])->id,
            'target_name' => 'Zona Prueba',
            'target_dealerships' => ['Bilbao'],
            'changes' => [
                'Nombre' => ['from' => null, 'to' => 'Zona Prueba'],
            ],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/backoffice/zonas')
            ->assertOk()
            ->assertSee('Ver logs', false);

        $this->actingAs($admin)
            ->get(ZoneResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Logs de zonas')
            ->assertSee('Zona Prueba')
            ->assertSee('Admin Principal');
    }

    public function test_zone_logs_show_empty_values_as_from_vacio_to_value(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $zone = Zone::query()->create(['name' => 'Zona Sant Boi']);

        ZoneActivityLog::query()->create([
            'action' => ZoneActivityLog::ACTION_UPDATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_zone_id' => $zone->id,
            'target_name' => $zone->name,
            'target_dealerships' => ['Sant Boi'],
            'changes' => [
                'Delegaciones' => [
                    'from' => null,
                    'to' => 'Sant Boi',
                ],
            ],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(ZoneResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Delegaciones: Vacío -> Sant Boi');
    }
}
