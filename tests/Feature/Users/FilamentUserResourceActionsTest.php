<?php

namespace Tests\Feature\Users;

use App\Models\User;
use App\Services\UserDeactivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
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

    public function test_create_user_shows_specific_error_when_enreach_extension_is_already_assigned(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Usuario existente',
            'email' => 'existente@example.com',
            'enreach_extension' => '1234',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(CreateUser::class)
            ->set('data.name', 'Nuevo usuario')
            ->set('data.email', 'nuevo@example.com')
            ->set('data.company_entry_date', '2024-01-02')
            ->set('data.job_position', 'Comercial')
            ->set('data.role', User::ROLE_USER)
            ->set('data.enreach_extension', '1234')
            ->call('create')
            ->assertHasFormErrors(['enreach_extension'])
            ->assertSee('Usuario existente')
            ->assertSee('Activo');
    }

    public function test_edit_user_shows_specific_error_when_enreach_extension_is_already_assigned(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Usuario conflictivo',
            'email' => 'conflictivo@example.com',
            'enreach_extension' => '4321',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Usuario a editar',
            'email' => 'editar@example.com',
            'enreach_extension' => '2001',
            'role' => User::ROLE_USER,
            'extra_role' => null,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->set('data.enreach_extension', '4321')
            ->call('save', false, false)
            ->assertHasFormErrors(['enreach_extension'])
            ->assertSee('Usuario conflictivo')
            ->assertSee('Activo');
    }
}
