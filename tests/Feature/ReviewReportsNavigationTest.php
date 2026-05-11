<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileMonthlySnapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewReportsNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_reviews_index_renames_reports_button_to_generic_reports(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $this->actingAs($user)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSee('Informes')
            ->assertDontSee('Informes mensuales');
    }

    public function test_reports_hub_shows_monthly_and_semiannual_entries(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $this->actingAs($user)
            ->get(route('reviews.reports'))
            ->assertOk()
            ->assertSee('Informes')
            ->assertSee('Informes mensuales')
            ->assertSee('Informes semestrales');
    }

    public function test_monthly_reports_show_a_button_to_the_comparativa_delegaciones_view(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $this->actingAs($user)
            ->get(route('reviews.reports.monthly'))
            ->assertOk()
            ->assertSee('Informes mensuales')
            ->assertSee('Comparativa delegaciones')
            ->assertSee(route('reviews.reports.monthly.comparison'))
            ->assertDontSee('Historial consolidado por delegación y mes.');
    }

    public function test_monthly_comparativa_delegaciones_view_shows_the_current_table(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor || Zaragoza',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'google_business_profile_location_title' => 'HR Motor || Zaragoza',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-11 12:00:00'));

        try {
            GoogleBusinessProfileMonthlySnapshot::query()->create([
                'dealership_id' => $dealership->id,
                'snapshot_month' => Carbon::parse('2026-05-01'),
                'total_reviews' => 120,
                'average_rating' => 4.35,
                'monthly_reviews' => 18,
                'monthly_average_rating' => 4.50,
                'unanswered_reviews' => 3,
                'captured_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);

            $this->actingAs($user)
                ->get(route('reviews.reports.monthly.comparison'))
                ->assertOk()
                ->assertSee('Comparativa delegaciones')
                ->assertSee('Zaragoza')
                ->assertSee('120')
                ->assertSee('4.35')
                ->assertSee('18')
                ->assertSee('4.50')
                ->assertSee('3');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_semiannual_reports_show_a_placeholder_view(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $this->actingAs($user)
            ->get(route('reviews.reports.semiannual'))
            ->assertOk()
            ->assertSee('Informes semestrales')
            ->assertSee('Resumen semestral')
            ->assertSee('Próximamente');
    }
}
