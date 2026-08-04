<?php

namespace Tests\Feature;

use App\Models\AdminPermissionGrant;
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

    public function test_navbar_does_not_include_the_web_interior_anymore(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'web-nav-check@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();

        $this->assertStringNotContainsString(route('tools.web'), $navbarHtml);
        $this->assertStringNotContainsString('Web HR Motor', $navbarHtml);
    }

    public function test_admin_in_role_viewer_mode_sees_admin_nav_when_the_visible_role_has_admin_access(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-viewer@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'notifications.manage',
            'user_id' => null,
            'group_id' => null,
            'group_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'granted_by_user_id' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['role_viewer.active_role' => User::ROLE_INFORMATION_TECHNOLOGY]);

        $navbarHtml = view('components.layout.navbar')->render();

        $this->assertStringContainsString('Admin', $navbarHtml);
        $this->assertStringContainsString(route('admin.index'), $navbarHtml);
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

    public function test_all_users_see_the_it_support_interior_link_in_the_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'it-support-link@example.com',
        ]);

        $this->actingAs($user);

        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringContainsString(route('it-tickets.index'), $footerHtml);
    }

    public function test_it_extra_role_users_see_tickets_in_the_navbar_and_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'email' => 'it-tickets@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringContainsString(route('tickets.index'), $navbarHtml);
        $this->assertStringContainsString(route('tickets.index'), $footerHtml);
    }

    public function test_admin_without_ticket_permission_does_not_see_tickets_in_the_navbar_or_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-no-ticket-permission@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringNotContainsString(route('tickets.index'), $navbarHtml);
        $this->assertStringNotContainsString(route('tickets.index'), $footerHtml);
        $this->get(route('tickets.index'))->assertForbidden();
    }

    public function test_admin_with_ticket_permission_but_without_it_role_still_cannot_see_tickets(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-with-ticket-permission@example.com',
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'tickets-it.manage',
            'user_id' => $user->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => null,
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringNotContainsString(route('tickets.index'), $navbarHtml);
        $this->assertStringNotContainsString(route('tickets.index'), $footerHtml);
        $this->get(route('tickets.index'))->assertForbidden();
    }

    public function test_regular_users_do_not_see_tickets_in_the_navbar_or_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'regular-tickets@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringNotContainsString(route('tickets.index'), $navbarHtml);
        $this->assertStringNotContainsString(route('tickets.index'), $footerHtml);
    }

    public function test_human_resources_extra_role_sees_curriculums_in_the_navbar_and_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringContainsString(route('curriculums.index'), $navbarHtml);
        $this->assertStringContainsString(route('curriculums.index'), $footerHtml);
        $this->assertStringContainsString('Currículums', $navbarHtml);
        $this->assertStringContainsString('Currículums', $footerHtml);
    }

    public function test_regular_user_does_not_see_curriculums_in_the_navbar_or_footer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'comercial-curriculums@example.com',
        ]);

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();
        $footerHtml = view('components.layout.footer')->render();

        $this->assertStringNotContainsString(route('curriculums.index'), $navbarHtml);
        $this->assertStringNotContainsString(route('curriculums.index'), $footerHtml);
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
        foreach ([User::ROLE_COMMERCIAL, User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'email' => strtolower(str_replace(' ', '-', $role)) . '@example.com',
            ]);

            $this->actingAs($user);

            $navbarHtml = view('components.layout.navbar')->render();

            $this->assertStringContainsString('Empresa', $navbarHtml);
            $this->assertStringContainsString(route('videos'), $navbarHtml);
            $this->assertStringContainsString(route('empresa.index'), $navbarHtml);
            $this->assertStringContainsString('Quiénes somos', $navbarHtml);
        }
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
        $this->assertStringContainsString('Quiénes somos', $navbarHtml);
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

    public function test_only_human_resources_extra_role_can_open_curriculums(): void
    {
        $allowedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh2@example.com',
        ]);

        $this->actingAs($allowedUser)
            ->get(route('curriculums.index'))
            ->assertOk();

        $deniedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'email' => 'comercial3@example.com',
        ]);

        $this->actingAs($deniedUser)
            ->get(route('curriculums.index'))
            ->assertForbidden();
    }
}
