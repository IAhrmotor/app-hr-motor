<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileReview;
use App\Models\User;
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
    }
}
