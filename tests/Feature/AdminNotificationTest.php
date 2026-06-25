<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminPriorityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_notification_form_shows_all_users_as_a_highlighted_first_card(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.notifications.create'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'data-notification-target-card="__all_users__"',
                'data-notification-target-card="' . User::ROLE_ADMIN . '"',
            ], false)
            ->assertSee('Todos los usuarios')
            ->assertSee('Destacado')
            ->assertSee('Se enviará a todos los usuarios activos del portal.');
    }

    public function test_admin_notification_form_sends_to_all_active_users_when_all_users_is_selected(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $activeCommercial = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
        ]);
        $activeManager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);
        $inactiveUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => false,
            'disabled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.notifications.store'), [
                'title' => 'Aviso general',
                'description' => 'Mensaje para toda la plantilla.',
                'roles' => ['__all_users__'],
            ])
            ->assertRedirect(route('admin.notifications.create'));

        Notification::assertSentTo($admin, AdminPriorityNotification::class);
        Notification::assertSentTo($activeCommercial, AdminPriorityNotification::class);
        Notification::assertSentTo($activeManager, AdminPriorityNotification::class);
        Notification::assertNotSentTo($inactiveUser, AdminPriorityNotification::class);

        $this->assertDatabaseHas('notification_activity_logs', [
            'title' => 'Aviso general',
            'recipient_count' => 3,
        ]);
    }
}
