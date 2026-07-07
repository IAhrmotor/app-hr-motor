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

    public function test_notification_navbar_refreshes_the_summary_when_the_realtime_counter_updates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $navbarHtml = view('components.layout.navbar')->render();

        $this->assertStringContainsString("if (payload.event === 'notifications.badge.updated')", $navbarHtml);
        $this->assertStringContainsString('void refreshNotifications();', $navbarHtml);
    }
}
