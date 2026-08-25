<?php

namespace Tests\Feature\Users;

use App\Models\User;
use App\Services\UserDeactivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentUserResourceActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_sees_delete_and_deactivate_actions_in_filament_users_table(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Usuario base',
            'role' => User::ROLE_USER,
            'extra_role' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/backoffice/usuarios');

        $response
            ->assertOk()
            ->assertSee('Borrar', false)
            ->assertSee('Desactivar', false);
    }

    public function test_manager_does_not_see_delete_action_and_can_only_deactivate_plain_users(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Usuario base',
            'role' => User::ROLE_USER,
            'extra_role' => null,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Admin de prueba',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get('/backoffice/usuarios');

        $response
            ->assertOk()
            ->assertDontSee('Borrar', false)
            ->assertSee('Desactivar', false);

        $service = app(UserDeactivationService::class);

        $plainUser = User::query()->where('role', User::ROLE_USER)->firstOrFail();
        $adminUser = User::query()->where('role', User::ROLE_ADMIN)->whereKeyNot($manager->id)->firstOrFail();

        $this->assertTrue($service->canDeactivate($manager, $plainUser));
        $this->assertFalse($service->canDeactivate($manager, $adminUser));
    }

    public function test_admin_can_download_users_csv_from_filament(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Usuario CSV',
            'email' => 'csv@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/usuarios/exportar-csv');

        $response
            ->assertOk()
            ->assertDownload()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
