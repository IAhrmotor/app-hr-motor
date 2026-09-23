<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AdminOverviewWidget;
use App\Models\CompanyChatGroup;
use App\Models\Dealership;
use App\Models\ItTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_shows_the_four_administrative_metrics(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
        User::factory()->create(['is_active' => true, 'disabled_at' => null]);
        User::factory()->create(['is_active' => false, 'disabled_at' => null]);
        User::factory()->create(['is_active' => true, 'disabled_at' => now()]);

        CompanyChatGroup::query()->create(['name' => 'Grupo KPI 1']);
        CompanyChatGroup::query()->create(['name' => 'Grupo KPI 2']);
        Dealership::factory()->count(3)->create();
        $expectedGroupCount = CompanyChatGroup::query()->count();
        foreach (['closed', 'clausurado', 'in_progress'] as $index => $status) {
            ItTicket::query()->create([
                'user_id' => $admin->id,
                'number' => 'KPI-' . ($index + 1),
                'tool' => 'general',
                'priority' => 'medium',
                'status' => $status,
                'title' => 'Incidencia KPI ' . ($index + 1),
                'description' => 'Incidencia de prueba para el dashboard.',
            ]);
        }

        $this->actingAs($admin)
            ->get('/backoffice')
            ->assertOk()
            ->assertSee(AdminOverviewWidget::class, false)
            ->assertSee('data-count-up-value="2"', false)
            ->assertSee('data-count-up-value="' . $expectedGroupCount . '"', false)
            ->assertSee('data-count-up-value="3"', false);

        Livewire::actingAs($admin);

        Livewire::test(AdminOverviewWidget::class)
            ->assertSee('Usuarios activos')
            ->assertSee('Grupos activos')
            ->assertSee('Delegaciones activas')
            ->assertSee('Incidencias resueltas')
            ->assertDontSee('Usuarios con acceso activo')
            ->assertDontSee('Todos los grupos existentes')
            ->assertDontSee('Todas las delegaciones existentes')
            ->assertDontSee('Total acumulado')
            ->assertSee('data-count-up-value="2"', false)
            ->assertSee('data-count-up-value="' . $expectedGroupCount . '"', false)
            ->assertSee('data-count-up-value="3"', false)
            ->assertSee('requestAnimationFrame', false)
            ->assertSee('prefers-reduced-motion', false)
            ->assertSee('countUpAnimated', false);
    }

    public function test_dashboard_is_not_accessible_without_backoffice_role(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/backoffice')
            ->assertForbidden();
    }
}
