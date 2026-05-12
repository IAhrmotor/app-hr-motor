<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_marketing_user_sees_reviews_in_the_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARKETING,
            'email' => 'marketing@example.com',
        ]);

        $this->actingAs($user);

        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringContainsString(route('reviews.index'), $footerHtml);
    }

    public function test_admin_user_does_not_see_reviews_in_the_navbar_by_default(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();

        $this->assertStringNotContainsString(route('reviews.index'), $navbarHtml);
    }
}
