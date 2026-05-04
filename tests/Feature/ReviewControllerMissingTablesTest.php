<?php

namespace Tests\Feature;

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
}
