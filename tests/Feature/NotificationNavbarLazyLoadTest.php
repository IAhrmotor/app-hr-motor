<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationNavbarLazyLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_notification_navbar_starts_with_a_lazy_load_placeholder(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();

        $this->assertStringContainsString('Abre la campana para cargar tus notificaciones', $navbarHtml);
        $this->assertStringContainsString('data-notification-list', $navbarHtml);
    }
}
