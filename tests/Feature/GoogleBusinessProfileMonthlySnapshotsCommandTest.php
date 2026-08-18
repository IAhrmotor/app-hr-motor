<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileMonthlySnapshot;
use App\Models\GoogleBusinessProfileReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleBusinessProfileMonthlySnapshotsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_monthly_snapshots_recomputes_a_historical_month_from_current_review_assignments(): void
    {
        $paterna = Dealership::query()->create([
            'name' => 'Paterna',
            'google_business_profile_location_name' => 'locations/2940196239892321357',
            'google_business_profile_location_title' => 'HR Motor || Valencia Paterna',
        ]);

        $valencia = Dealership::query()->create([
            'name' => 'Valencia',
            'google_business_profile_location_name' => 'locations/4081233659940531681',
            'google_business_profile_location_title' => 'HR Motor || València',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $paterna->id,
            'location_name' => 'locations/2940196239892321357',
            'location_title' => 'HR Motor || Valencia Paterna',
            'review_name' => 'reviews-paterna-july-1',
            'reviewer_name' => 'Cliente P1',
            'rating' => 5,
            'reply_comment' => null,
            'review_created_at' => '2026-07-03 10:00:00',
            'synced_at' => '2026-08-04 10:00:00',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $paterna->id,
            'location_name' => 'locations/2940196239892321357',
            'location_title' => 'HR Motor || Valencia Paterna',
            'review_name' => 'reviews-paterna-july-2',
            'reviewer_name' => 'Cliente P2',
            'rating' => 4,
            'reply_comment' => 'Gracias',
            'review_created_at' => '2026-07-14 11:00:00',
            'synced_at' => '2026-08-04 10:00:00',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $paterna->id,
            'location_name' => 'locations/2940196239892321357',
            'location_title' => 'HR Motor || Valencia Paterna',
            'review_name' => 'reviews-paterna-august',
            'reviewer_name' => 'Cliente P3',
            'rating' => 1,
            'reply_comment' => null,
            'review_created_at' => '2026-08-02 12:00:00',
            'synced_at' => '2026-08-04 10:00:00',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $valencia->id,
            'location_name' => 'locations/4081233659940531681',
            'location_title' => 'HR Motor || València',
            'review_name' => 'reviews-valencia-july-1',
            'reviewer_name' => 'Cliente V1',
            'rating' => 3,
            'reply_comment' => null,
            'review_created_at' => '2026-07-08 09:00:00',
            'synced_at' => '2026-08-04 10:00:00',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $valencia->id,
            'location_name' => 'locations/4081233659940531681',
            'location_title' => 'HR Motor || València',
            'review_name' => 'reviews-valencia-august',
            'reviewer_name' => 'Cliente V2',
            'rating' => 5,
            'reply_comment' => null,
            'review_created_at' => '2026-08-01 09:00:00',
            'synced_at' => '2026-08-04 10:00:00',
        ]);

        GoogleBusinessProfileMonthlySnapshot::query()->create([
            'dealership_id' => $valencia->id,
            'snapshot_month' => '2026-07-01',
            'total_reviews' => 999,
            'average_rating' => 1.00,
            'monthly_reviews' => 999,
            'monthly_average_rating' => 1.00,
            'unanswered_reviews' => 999,
            'captured_at' => '2026-08-04 10:00:00',
        ]);

        $this->artisan('google-business-profile:rebuild-monthly-snapshots', [
            'month' => '2026-07',
        ])
            ->expectsOutput('Snapshots mensuales reconstruidos para 2026-07. Registros actualizados: 2.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('google_business_profile_monthly_snapshots', [
            'dealership_id' => $paterna->id,
            'snapshot_month' => '2026-07-01 00:00:00',
            'total_reviews' => 2,
            'monthly_reviews' => 2,
            'unanswered_reviews' => 1,
        ]);

        $this->assertDatabaseHas('google_business_profile_monthly_snapshots', [
            'dealership_id' => $valencia->id,
            'snapshot_month' => '2026-07-01 00:00:00',
            'total_reviews' => 1,
            'monthly_reviews' => 1,
            'unanswered_reviews' => 1,
        ]);
    }
}
