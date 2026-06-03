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

    public function test_management_user_sees_informes_in_the_navbar_and_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MANAGEMENT,
            'email' => 'gerencia@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringContainsString(route('tools.informes'), $navbarHtml);
        $this->assertStringContainsString(route('tools.informes'), $footerHtml);
    }

    public function test_regular_user_does_not_see_informes_in_the_navbar_or_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'comercial@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringNotContainsString(route('tools.informes'), $navbarHtml);
        $this->assertStringNotContainsString(route('tools.informes'), $footerHtml);
    }

    public function test_only_management_and_area_manager_can_open_informes(): void
    {
        $allowedUser = User::factory()->create([
            'role' => User::ROLE_MANAGEMENT,
            'email' => 'gerencia2@example.com',
        ]);

        $this->actingAs($allowedUser)
            ->get(route('tools.informes'))
            ->assertOk();

        $deniedUser = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'comercial2@example.com',
        ]);

        $this->actingAs($deniedUser)
            ->get(route('tools.informes'))
            ->assertForbidden();
    }
}
