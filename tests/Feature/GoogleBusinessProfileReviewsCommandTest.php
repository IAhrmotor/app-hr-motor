<?php

namespace Tests\Feature;

use App\Models\GoogleBusinessProfileReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleBusinessProfileReviewsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_lists_only_reviews_for_the_requested_month_and_location(): void
    {
        GoogleBusinessProfileReview::query()->create([
            'location_title' => 'HR Motor || Zaragoza',
            'location_name' => 'accounts/123/locations/zaragoza',
            'review_name' => 'accounts/123/locations/zaragoza/reviews/1',
            'reviewer_name' => 'Ana',
            'rating' => 4,
            'comment' => 'Bien',
            'review_created_at' => '2026-05-10 10:00:00',
            'review_updated_at' => '2026-05-10 10:00:00',
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Bien'],
        ]);

        GoogleBusinessProfileReview::query()->create([
            'location_title' => 'HR Motor || Zaragoza',
            'location_name' => 'accounts/123/locations/zaragoza',
            'review_name' => 'accounts/123/locations/zaragoza/reviews/2',
            'reviewer_name' => 'Luis',
            'rating' => 5,
            'comment' => 'Genial',
            'review_created_at' => '2026-05-11 10:00:00',
            'review_updated_at' => '2026-05-11 10:00:00',
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Genial'],
        ]);

        GoogleBusinessProfileReview::query()->create([
            'location_title' => 'HR Motor || Zaragoza',
            'location_name' => 'accounts/123/locations/zaragoza',
            'review_name' => 'accounts/123/locations/zaragoza/reviews/3',
            'reviewer_name' => 'Marta',
            'rating' => 1,
            'comment' => 'Muy mal',
            'review_created_at' => '2026-06-01 10:00:00',
            'review_updated_at' => '2026-06-01 10:00:00',
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Muy mal'],
        ]);

        $this->artisan('google-business-profile:reviews', [
            'month' => '05-26',
            'location' => 'HR Motor || Zaragoza',
        ])
            ->expectsOutput('Delegación: HR Motor || Zaragoza')
            ->expectsOutput('Mes: 05-26')
            ->expectsOutput('Reseñas: 2')
            ->expectsOutput('Media: 4.50')
            ->expectsOutputToContain('Ana')
            ->expectsOutputToContain('Luis')
            ->assertExitCode(0);
    }
}
