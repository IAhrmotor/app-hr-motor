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

    public function test_all_users_see_quienes_somos_in_the_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'footer-empresa@example.com',
            'extra_role' => null,
        ]);

        $this->actingAs($user);

        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringContainsString(route('empresa.index'), $footerHtml);
        $this->assertStringContainsString('Quiénes somos', $footerHtml);
    }

    public function test_users_with_video_access_see_videos_and_quienes_somos_under_empresa_in_the_navbar(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'videos@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();

        $this->assertStringContainsString('Empresa', $navbarHtml);
        $this->assertStringContainsString(route('videos'), $navbarHtml);
        $this->assertStringContainsString(route('empresa.index'), $navbarHtml);
        $this->assertStringContainsString('Qui&eacute;nes somos', $navbarHtml);
    }

    public function test_users_without_video_access_see_quienes_somos_under_empresa_in_the_navbar(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'empresa@example.com',
            'extra_role' => null,
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();

        $this->assertStringContainsString('Empresa', $navbarHtml);
        $this->assertStringContainsString(route('empresa.index'), $navbarHtml);
        $this->assertStringContainsString('Qui&eacute;nes somos', $navbarHtml);
        $this->assertStringNotContainsString(route('videos'), $navbarHtml);
    }

    public function test_empresa_page_is_visible_for_authenticated_users(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'empresa-page@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('empresa.index'))
            ->assertOk()
            ->assertSee('Quiénes somos HR Motor', false)
            ->assertSee('Mapa', false);
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
