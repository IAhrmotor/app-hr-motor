<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileReview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewControllerMissingTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_reviews_index_does_not_crash_when_review_tables_are_missing(): void
    {
        Schema::dropIfExists('google_business_profile_reviews');
        Schema::dropIfExists('google_business_profile_monthly_snapshots');

        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $this->actingAs($user)
            ->get(route('reviews.index'))
            ->assertOk();
    }

    public function test_reviews_index_and_dealership_pages_hide_salamanca_locations(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $visibleDealership = Dealership::query()->create([
            'name' => 'HR Motor || Zaragoza',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'google_business_profile_location_title' => 'HR Motor || Zaragoza',
        ]);

        $hiddenDealership = Dealership::query()->create([
            'name' => 'HR Motor || Salamanca',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/salamanca',
            'google_business_profile_location_title' => 'HR Motor || Salamanca',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $visibleDealership->id,
            'location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'location_title' => 'HR Motor || Zaragoza',
            'review_name' => 'accounts/117678944517959788740/locations/zaragoza/reviews/visible',
            'reviewer_name' => 'Visible',
            'rating' => 5,
            'comment' => 'Visible review',
            'review_created_at' => now(),
            'review_updated_at' => now(),
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Visible review'],
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $hiddenDealership->id,
            'location_name' => 'accounts/117678944517959788740/locations/salamanca',
            'location_title' => 'HR Motor || Salamanca',
            'review_name' => 'accounts/117678944517959788740/locations/salamanca/reviews/hidden',
            'reviewer_name' => 'Hidden',
            'rating' => 5,
            'comment' => 'Hidden review',
            'review_created_at' => now(),
            'review_updated_at' => now(),
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Hidden review'],
        ]);

        $this->actingAs($user)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSee('Zaragoza')
            ->assertDontSee('Salamanca');

        $this->actingAs($user)
            ->get(route('reviews.show', $visibleDealership))
            ->assertOk()
            ->assertSee('Visible review')
            ->assertDontSee('Hidden review');

        $this->actingAs($user)
            ->get(route('reviews.show', $hiddenDealership))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('reviews.location', ['locationKey' => rtrim(strtr(base64_encode('accounts/117678944517959788740/locations/salamanca'), '+/', '-_'), '=')]))
            ->assertNotFound();
    }

    public function test_dealership_reviews_are_paginated_and_answered_reviews_do_not_show_the_reply_form(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor || Zaragoza',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'google_business_profile_location_title' => 'HR Motor || Zaragoza',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $dealership->id,
            'location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'location_title' => 'HR Motor || Zaragoza',
            'review_name' => 'accounts/117678944517959788740/locations/zaragoza/reviews/answered',
            'reviewer_name' => 'Answered',
            'rating' => 5,
            'comment' => 'Answered review',
            'reply_comment' => 'Respuesta ya publicada',
            'review_created_at' => now()->addMinutes(11),
            'review_updated_at' => now()->addMinutes(11),
            'reply_updated_at' => now()->addMinutes(12),
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Answered review'],
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $label = str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            GoogleBusinessProfileReview::query()->create([
                'dealership_id' => $dealership->id,
                'location_name' => 'accounts/117678944517959788740/locations/zaragoza',
                'location_title' => 'HR Motor || Zaragoza',
                'review_name' => 'accounts/117678944517959788740/locations/zaragoza/reviews/review-' . $label,
                'reviewer_name' => 'Review ' . $label,
                'rating' => 5,
                'comment' => 'Review ' . $label,
                'review_created_at' => now()->addMinutes($i),
                'review_updated_at' => now()->addMinutes($i),
                'synced_at' => now(),
                'raw_payload' => ['comment' => 'Review ' . $label],
            ]);
        }

        $this->actingAs($user)
            ->get(route('reviews.show', $dealership))
            ->assertOk()
            ->assertSee('Ya respondida')
            ->assertSee('Pendiente de responder')
            ->assertSee('Publicar respuesta')
            ->assertSee('Review 10')
            ->assertDontSee('Review 01');

        $this->actingAs($user)
            ->get(route('reviews.show', $dealership) . '?page=2')
            ->assertOk()
            ->assertSee('Review 01')
            ->assertDontSee('Review 10');
    }

    public function test_dealership_show_page_renders_a_six_month_historical_line_chart(): void
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
            $ratingsByMonth = [
                '2025-12-15 10:00:00' => 2,
                '2026-01-15 10:00:00' => 3,
                '2026-02-15 10:00:00' => 4,
                '2026-03-15 10:00:00' => 5,
                '2026-04-15 10:00:00' => 4,
                '2026-05-15 10:00:00' => 3,
            ];

            foreach ($ratingsByMonth as $date => $rating) {
                GoogleBusinessProfileReview::query()->create([
                    'dealership_id' => $dealership->id,
                    'location_name' => 'accounts/117678944517959788740/locations/zaragoza',
                    'location_title' => 'HR Motor || Zaragoza',
                    'review_name' => 'accounts/117678944517959788740/locations/zaragoza/reviews/' . str_replace([' ', ':'], ['-', ''], $date),
                    'reviewer_name' => 'Review ' . $rating,
                    'rating' => $rating,
                    'comment' => 'Review for ' . $date,
                    'review_created_at' => Carbon::parse($date),
                    'review_updated_at' => Carbon::parse($date),
                    'synced_at' => now(),
                    'raw_payload' => ['comment' => 'Review for ' . $date],
                ]);
            }

            $this->actingAs($user)
                ->get(route('reviews.show', $dealership))
                ->assertOk()
                ->assertSee('Evoluci&oacute;n hist&oacute;rica', false)
                ->assertSee('Media mensual de los &uacute;ltimos seis meses, incluyendo el actual.', false)
                ->assertSee('12/2025')
                ->assertSee('01/2026')
                ->assertSee('02/2026')
                ->assertSee('03/2026')
                ->assertSee('04/2026')
                ->assertSee('05/2026')
                ->assertSee('2.00')
                ->assertSee('3.00')
                ->assertSee('4.00')
                ->assertSee('5.00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_answered_reviews_do_not_show_the_reply_editor(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
        ]);

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor || Zaragoza',
            'google_business_profile_location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'google_business_profile_location_title' => 'HR Motor || Zaragoza',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $dealership->id,
            'location_name' => 'accounts/117678944517959788740/locations/zaragoza',
            'location_title' => 'HR Motor || Zaragoza',
            'review_name' => 'accounts/117678944517959788740/locations/zaragoza/reviews/answered-only',
            'reviewer_name' => 'Answered',
            'rating' => 5,
            'comment' => 'Answered review',
            'reply_comment' => 'Respuesta ya publicada',
            'review_created_at' => now(),
            'review_updated_at' => now(),
            'reply_updated_at' => now(),
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Answered review'],
        ]);

        $this->actingAs($user)
            ->get(route('reviews.show', $dealership))
            ->assertOk()
            ->assertSee('Ya respondida')
            ->assertDontSee('Pendiente de responder')
            ->assertDontSee('Publicar respuesta')
            ->assertDontSee('Responder');
    }
}
