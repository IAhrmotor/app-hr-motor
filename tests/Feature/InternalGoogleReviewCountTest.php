<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalGoogleReviewCountTest extends TestCase
{
    use RefreshDatabase;

    private function basicAuthHeader(string $user, string $password): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($user . ':' . $password),
        ];
    }

    public function test_endpoint_rejects_requests_without_basic_auth(): void
    {
        config()->set('internal.google_reviews.user', 'usuario-interno');
        config()->set('internal.google_reviews.password', 'clave-segura');

        $this->getJson('/api/internal/google-reviews/count?month=05-26&location=HR%20Motor%20Alcobendas')
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized',
            ]);
    }

    public function test_endpoint_rejects_invalid_credentials(): void
    {
        config()->set('internal.google_reviews.user', 'usuario-interno');
        config()->set('internal.google_reviews.password', 'clave-segura');

        $this->getJson('/api/internal/google-reviews/count?month=05-26&location=HR%20Motor%20Alcobendas', [
            'Authorization' => 'Basic ' . base64_encode('usuario-interno:clave-incorrecta'),
        ])->assertStatus(401);
    }

    public function test_endpoint_validates_missing_and_invalid_parameters(): void
    {
        config()->set('internal.google_reviews.user', 'usuario-interno');
        config()->set('internal.google_reviews.password', 'clave-segura');

        $this->getJson('/api/internal/google-reviews/count', $this->basicAuthHeader('usuario-interno', 'clave-segura'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month', 'location']);

        $this->getJson('/api/internal/google-reviews/count?month=2026-05&location=HR%20Motor%20Alcobendas', $this->basicAuthHeader('usuario-interno', 'clave-segura'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);
    }

    public function test_endpoint_returns_404_when_location_does_not_exist_exactly(): void
    {
        config()->set('internal.google_reviews.user', 'usuario-interno');
        config()->set('internal.google_reviews.password', 'clave-segura');

        Dealership::query()->create([
            'name' => 'HR Motor Zaragoza',
            'google_business_profile_location_title' => 'HR Motor Zaragoza',
            'google_business_profile_location_name' => 'accounts/123/locations/zaragoza',
        ]);

        $this->getJson('/api/internal/google-reviews/count?month=05-26&location=HR%20Motor%20Alcobendas', $this->basicAuthHeader('usuario-interno', 'clave-segura'))
            ->assertStatus(404)
            ->assertJson([
                'message' => 'Location not found',
            ]);
    }

    public function test_endpoint_returns_zero_when_location_exists_but_has_no_reviews_in_that_month(): void
    {
        config()->set('internal.google_reviews.user', 'usuario-interno');
        config()->set('internal.google_reviews.password', 'clave-segura');

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor Alcobendas',
            'google_business_profile_location_title' => 'HR Motor Alcobendas',
            'google_business_profile_location_name' => 'accounts/123/locations/alcobendas',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $dealership->id,
            'location_title' => 'HR Motor Alcobendas',
            'location_name' => 'accounts/123/locations/alcobendas',
            'review_name' => 'accounts/123/locations/alcobendas/reviews/old',
            'reviewer_name' => 'Cliente',
            'rating' => 5,
            'comment' => 'Muy bien',
            'review_created_at' => now()->subMonths(2),
            'review_updated_at' => now()->subMonths(2),
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Muy bien'],
        ]);

        $this->getJson('/api/internal/google-reviews/count?month=05-26&location=HR%20Motor%20Alcobendas', $this->basicAuthHeader('usuario-interno', 'clave-segura'))
            ->assertOk()
            ->assertJson([
                'month' => '05-26',
                'location' => 'HR Motor Alcobendas',
                'reviews_count' => 0,
                'average_rating' => null,
            ]);
    }

    public function test_endpoint_returns_reviews_count_and_average_for_the_requested_month(): void
    {
        config()->set('internal.google_reviews.user', 'usuario-interno');
        config()->set('internal.google_reviews.password', 'clave-segura');

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor Alcobendas',
            'google_business_profile_location_title' => 'HR Motor Alcobendas',
            'google_business_profile_location_name' => 'accounts/123/locations/alcobendas',
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $dealership->id,
            'location_title' => 'HR Motor Alcobendas',
            'location_name' => 'accounts/123/locations/alcobendas',
            'review_name' => 'accounts/123/locations/alcobendas/reviews/1',
            'reviewer_name' => 'Cliente 1',
            'rating' => 4,
            'comment' => 'Bien',
            'review_created_at' => '2026-05-10 12:00:00',
            'review_updated_at' => '2026-05-10 12:00:00',
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Bien'],
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $dealership->id,
            'location_title' => 'HR Motor Alcobendas',
            'location_name' => 'accounts/123/locations/alcobendas',
            'review_name' => 'accounts/123/locations/alcobendas/reviews/2',
            'reviewer_name' => 'Cliente 2',
            'rating' => 5,
            'comment' => 'Genial',
            'review_created_at' => '2026-05-20 12:00:00',
            'review_updated_at' => '2026-05-20 12:00:00',
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Genial'],
        ]);

        GoogleBusinessProfileReview::query()->create([
            'dealership_id' => $dealership->id,
            'location_title' => 'HR Motor Alcobendas',
            'location_name' => 'accounts/123/locations/alcobendas',
            'review_name' => 'accounts/123/locations/alcobendas/reviews/3',
            'reviewer_name' => 'Cliente 3',
            'rating' => 3,
            'comment' => 'Normal',
            'review_created_at' => '2026-06-01 12:00:00',
            'review_updated_at' => '2026-06-01 12:00:00',
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Normal'],
        ]);

        $this->getJson('/api/internal/google-reviews/count?month=05-26&location=HR%20Motor%20Alcobendas', $this->basicAuthHeader('usuario-interno', 'clave-segura'))
            ->assertOk()
            ->assertJson([
                'month' => '05-26',
                'location' => 'HR Motor Alcobendas',
                'reviews_count' => 2,
                'average_rating' => 4.5,
            ]);
    }

    public function test_endpoint_works_for_unlinked_locations_that_only_exist_in_reviews_like_badajoz(): void
    {
        config()->set('internal.google_reviews.user', 'usuario-interno');
        config()->set('internal.google_reviews.password', 'clave-segura');

        GoogleBusinessProfileReview::query()->create([
            'location_title' => 'HR Motor || Badajoz',
            'location_name' => 'accounts/123/locations/badajoz',
            'review_name' => 'accounts/123/locations/badajoz/reviews/1',
            'reviewer_name' => 'Cliente Badajoz',
            'rating' => 5,
            'comment' => 'Muy bien',
            'review_created_at' => '2026-05-12 10:00:00',
            'review_updated_at' => '2026-05-12 10:00:00',
            'synced_at' => now(),
            'raw_payload' => ['comment' => 'Muy bien'],
        ]);

        $this->getJson('/api/internal/google-reviews/count?month=05-26&location=HR%20Motor%20%7C%7C%20Badajoz', $this->basicAuthHeader('usuario-interno', 'clave-segura'))
            ->assertOk()
            ->assertJson([
                'month' => '05-26',
                'location' => 'HR Motor || Badajoz',
                'reviews_count' => 1,
                'average_rating' => 5.0,
            ]);
    }
}
