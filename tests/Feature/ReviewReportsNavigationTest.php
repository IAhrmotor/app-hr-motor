<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileReview;
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
            ->assertSee('Volver a reseñas')
            ->assertSee('Informes')
            ->assertSee('Informes mensuales')
            ->assertSee('Informes semestrales');
    }

    public function test_monthly_reports_show_only_the_comparativa_delegaciones_option(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $this->actingAs($user)
            ->get(route('reviews.reports.monthly'))
            ->assertOk()
            ->assertSee('Informes mensuales')
            ->assertSee('Comparativa delegaciones tabla')
            ->assertSee('Comparativa delegaciones roscos')
            ->assertSee(route('reviews.reports.monthly.comparison'))
            ->assertSee(route('reviews.reports.monthly.roscos'))
            ->assertSee('Volver')
            ->assertDontSee('Informes semestrales');
    }

    public function test_monthly_comparativa_delegaciones_view_filters_by_selected_month(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor || Zaragoza',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'google_business_profile_location_title' => 'HR Motor || Zaragoza',
        ]);
        $dealershipMayTwo = Dealership::query()->create([
            'name' => 'HR Motor || Valencia',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/valencia',
            'google_business_profile_location_title' => 'HR Motor || Valencia',
        ]);
        $dealershipApril = Dealership::query()->create([
            'name' => 'HR Motor || Sevilla',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/sevilla',
            'google_business_profile_location_title' => 'HR Motor || Sevilla',
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

            GoogleBusinessProfileMonthlySnapshot::query()->create([
                'dealership_id' => $dealershipMayTwo->id,
                'snapshot_month' => Carbon::parse('2026-05-01'),
                'total_reviews' => 130,
                'average_rating' => 4.80,
                'monthly_reviews' => 15,
                'monthly_average_rating' => 4.90,
                'unanswered_reviews' => 2,
                'captured_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);

            GoogleBusinessProfileMonthlySnapshot::query()->create([
                'dealership_id' => $dealershipApril->id,
                'snapshot_month' => Carbon::parse('2026-04-01'),
                'total_reviews' => 90,
                'average_rating' => 4.10,
                'monthly_reviews' => 12,
                'monthly_average_rating' => 4.00,
                'unanswered_reviews' => 5,
                'captured_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);

            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealership->id,
                'location_name' => 'locations/zaragoza',
                'location_title' => 'HR Motor || Zaragoza',
                'review_name' => 'reviews-zaragoza-1',
                'reviewer_name' => 'Cliente 1',
                'rating' => 1,
                'review_created_at' => Carbon::parse('2026-05-03 10:00:00'),
                'synced_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);
            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealership->id,
                'location_name' => 'locations/zaragoza',
                'location_title' => 'HR Motor || Zaragoza',
                'review_name' => 'reviews-zaragoza-2',
                'reviewer_name' => 'Cliente 2',
                'rating' => 2,
                'review_created_at' => Carbon::parse('2026-05-04 11:00:00'),
                'synced_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);
            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealership->id,
                'location_name' => 'locations/zaragoza',
                'location_title' => 'HR Motor || Zaragoza',
                'review_name' => 'reviews-zaragoza-3',
                'reviewer_name' => 'Cliente 3',
                'rating' => 3,
                'review_created_at' => Carbon::parse('2026-05-05 12:00:00'),
                'synced_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);
            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealership->id,
                'location_name' => 'locations/zaragoza',
                'location_title' => 'HR Motor || Zaragoza',
                'review_name' => 'reviews-zaragoza-4',
                'reviewer_name' => 'Cliente 4',
                'rating' => 5,
                'review_created_at' => Carbon::parse('2026-05-06 13:00:00'),
                'synced_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);

            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealershipMayTwo->id,
                'location_name' => 'locations/valencia',
                'location_title' => 'HR Motor || Valencia',
                'review_name' => 'reviews-valencia-1',
                'reviewer_name' => 'Cliente 5',
                'rating' => 4,
                'review_created_at' => Carbon::parse('2026-05-07 14:00:00'),
                'synced_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);
            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealershipMayTwo->id,
                'location_name' => 'locations/valencia',
                'location_title' => 'HR Motor || Valencia',
                'review_name' => 'reviews-valencia-2',
                'reviewer_name' => 'Cliente 6',
                'rating' => 4,
                'review_created_at' => Carbon::parse('2026-05-08 15:00:00'),
                'synced_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);
            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealershipMayTwo->id,
                'location_name' => 'locations/valencia',
                'location_title' => 'HR Motor || Valencia',
                'review_name' => 'reviews-valencia-3',
                'reviewer_name' => 'Cliente 7',
                'rating' => 5,
                'review_created_at' => Carbon::parse('2026-05-09 16:00:00'),
                'synced_at' => Carbon::parse('2026-05-11 12:00:00'),
            ]);

            $this->actingAs($user)
                ->get(route('reviews.reports.monthly.comparison', ['month' => '2026-05']))
                ->assertOk()
                ->assertSee('Comparativa delegaciones tabla')
                ->assertSee('Zaragoza')
                ->assertSee('Valencia')
                ->assertSee('120')
                ->assertSee('4.35')
                ->assertSee('130')
                ->assertSee('4.80')
                ->assertSee('18')
                ->assertSee('4.50')
                ->assertSee('3')
                ->assertSee('15')
                ->assertSee('4.90')
                ->assertSee('2')
                ->assertSeeInOrder(['Valencia', 'Zaragoza']);

            $this->actingAs($user)
                ->get(route('reviews.reports.monthly.comparison', ['month' => '2026-05', 'sort' => 'average_rating', 'direction' => 'desc']))
                ->assertOk()
                ->assertSeeInOrder(['Valencia', 'Zaragoza'])
                ->assertSee(route('reviews.reports.monthly.comparison', ['month' => '2026-05', 'sort' => 'average_rating', 'direction' => 'asc']));

            $this->actingAs($user)
                ->get(route('reviews.reports.monthly.roscos', ['month' => '2026-05']))
                ->assertOk()
                ->assertSee('Comparativa delegaciones roscos')
                ->assertSee('Zaragoza')
                ->assertSee('Valencia')
                ->assertSee('Total: 4')
                ->assertSee('Total: 3')
                ->assertSee('1-2: 2')
                ->assertSee('3: 1')
                ->assertSee('4-5: 1')
                ->assertSee('1-2: 0')
                ->assertSee('3: 0')
                ->assertSee('4-5: 3')
                ->assertDontSee('Media mes')
                ->assertDontSee('Sin responder');

            $this->actingAs($user)
                ->get(route('reviews.reports.monthly.comparison', ['month' => '2026-04']))
                ->assertOk()
                ->assertSee('04/2026')
                ->assertSee('Sevilla')
                ->assertSee('90')
                ->assertSee('4.10')
                ->assertSee('12')
                ->assertSee('4.00')
                ->assertSee('5')
                ->assertDontSee('Zaragoza');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_semiannual_reports_show_only_the_semiannual_option(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $this->actingAs($user)
            ->get(route('reviews.reports.semiannual'))
            ->assertOk()
            ->assertSee('Informes semestrales')
            ->assertSee('Resumen semestral')
            ->assertSee('Próximamente')
            ->assertSee('Volver')
            ->assertDontSee('Informes mensuales');
    }
}
