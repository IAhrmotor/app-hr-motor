<?php

namespace Tests\Feature;

use App\Events\UserNotificationBadgeUpdated;
use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use App\Notifications\CompanyChatMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationBadgeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_creating_a_database_notification_broadcasts_the_updated_badge_count(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $capturedEvent = null;

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $message = CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Nuevo mensaje',
        ]);

        Event::fakeExcept([
            'eloquent.created: ' . DatabaseNotification::class,
            UserNotificationBadgeUpdated::class,
        ]);

        Event::listen(UserNotificationBadgeUpdated::class, function (UserNotificationBadgeUpdated $event) use (&$capturedEvent): void {
            $capturedEvent = $event;
        });

        $recipient->notify(new CompanyChatMessageNotification($conversation, $message, $sender));

        $this->assertSame(1, $recipient->unreadNotifications()->count());
        $this->assertInstanceOf(UserNotificationBadgeUpdated::class, $capturedEvent);
        $this->assertSame($recipient->id, $capturedEvent->userId);
        $this->assertSame(1, $capturedEvent->count);
    }

    public function test_marking_a_notification_as_read_broadcasts_the_updated_badge_count(): void
    {
        $user = User::factory()->create();
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'custom.notice',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Nuevo aviso',
                'description' => 'Tienes algo pendiente',
                'link_url' => route('home'),
                'link_label' => 'Abrir',
                'priority' => false,
            ], JSON_UNESCAPED_UNICODE),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Event::fake();

        $this->actingAs($user)
            ->get(route('notifications.show', $notificationId))
            ->assertRedirect(route('home'));

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());

        Event::assertDispatched(UserNotificationBadgeUpdated::class, function (UserNotificationBadgeUpdated $event) use ($user): bool {
            return $event->userId === $user->id
                && $event->count === 0;
        });
    }
}
