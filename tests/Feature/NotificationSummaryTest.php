<?php

namespace Tests\Feature;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use App\Notifications\CompanyChatMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_notification_summary_returns_unread_notifications_for_the_current_user(): void
    {
        $user = User::factory()->create();

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'forum.thread.created',
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

        $this->actingAs($user)
            ->getJson(route('notifications.summary'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Nuevo aviso');
    }

    public function test_notification_summary_keeps_total_unread_count_even_when_chat_notifications_are_grouped(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        for ($index = 1; $index <= 5; $index++) {
            $message = CompanyChatMessage::query()->create([
                'company_chat_conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => 'Mensaje ' . $index,
            ]);

            $recipient->notify(new CompanyChatMessageNotification($conversation, $message, $sender));
        }

        $this->actingAs($recipient)
            ->getJson(route('notifications.summary'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('unread_count', 5)
            ->assertJsonPath('notifications.0.message_count', 5);
    }
}
