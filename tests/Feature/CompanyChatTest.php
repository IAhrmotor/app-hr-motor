<?php

namespace Tests\Feature;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use App\Notifications\CompanyChatMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CompanyChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_open_the_chat_and_start_a_conversation_with_any_active_member(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create([
            'name' => 'Marta Test',
        ]);

        $response = $this->actingAs($sender)->get(route('chat.beta', ['recipient' => $recipient->id]));

        $conversation = CompanyChatConversation::query()->first();

        $response
            ->assertRedirect(route('chat.beta', ['conversation' => $conversation?->id]));

        $this->assertNotNull($conversation);
        $this->assertSame(min($sender->id, $recipient->id), $conversation->user_one_id);
        $this->assertSame(max($sender->id, $recipient->id), $conversation->user_two_id);
    }

    public function test_user_can_send_messages_and_they_are_marked_as_read_when_opening_the_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create([
            'name' => 'Lucia Test',
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $message = CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Hola, puedes revisar el expediente de hoy?',
        ]);

        $this->actingAs($recipient)
            ->get(route('chat.beta', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Hola, puedes revisar el expediente de hoy?');

        $message->refresh();

        $this->assertNotNull($message->read_at);

        $this->actingAs($recipient)
            ->post(route('chat.beta.messages.store', $conversation), [
                'body' => 'Si, lo miro ahora mismo.',
            ])
            ->assertRedirect(route('chat.beta', ['conversation' => $conversation->id]));

        $conversation->refresh();
        $sentMessage = CompanyChatMessage::query()->latest('id')->first();

        $this->assertSame('Si, lo miro ahora mismo.', $conversation->last_message_excerpt);
        $this->assertNotNull($conversation->last_message_at);
        $this->assertDatabaseCount('company_chat_messages', 2);
        $this->assertNotNull($sentMessage);
        $this->assertNull($sentMessage->read_at);
        $this->assertDatabaseHas('notifications', [
            'type' => CompanyChatMessageNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $sender->id,
        ]);
    }

    public function test_chat_messages_endpoint_returns_json_and_marks_incoming_messages_as_read(): void
    {
        $sender = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
        ]);
        $recipient = User::factory()->create([
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $message = CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Mensaje de prueba',
        ]);

        $response = $this->actingAs($recipient)->getJson(route('chat.beta.messages.index', $conversation));

        $response
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Mensaje de prueba')
            ->assertJsonPath('messages.0.sender_chat_role_label', 'Informática');

        $message->refresh();

        $this->assertNotNull($message->read_at);
        $this->assertSame(0, $recipient->unreadNotifications()->count());
    }

    public function test_chat_summary_returns_conversation_metadata_and_unread_counts(): void
    {
        $sender = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
        ]);
        $recipient = User::factory()->create([
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $this->actingAs($sender)
            ->post(route('chat.beta.messages.store', $conversation), [
                'body' => 'Mensaje sin leer',
            ])
            ->assertRedirect(route('chat.beta', ['conversation' => $conversation->id]));

        $response = $this->actingAs($recipient)->getJson(route('chat.beta.summary'));

        $response
            ->assertOk()
            ->assertJsonPath('conversations.0.partner_name', $sender->name)
            ->assertJsonPath('conversations.0.partner_chat_role_label', 'Informática')
            ->assertJsonPath('conversations.0.unread_messages_count', 1)
            ->assertJsonPath('unread_messages_total', 1);

        $this->assertSame(1, $recipient->unreadNotifications()->count());
    }

    public function test_chat_notifications_are_grouped_in_the_notifications_summary_and_marked_read_together(): void
    {
        $sender = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
        ]);
        $recipient = User::factory()->create([
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $this->actingAs($sender)
            ->post(route('chat.beta.messages.store', $conversation), [
                'body' => 'Primer mensaje',
            ])
            ->assertRedirect(route('chat.beta', ['conversation' => $conversation->id]));

        $this->actingAs($sender)
            ->post(route('chat.beta.messages.store', $conversation), [
                'body' => 'Segundo mensaje',
            ])
            ->assertRedirect(route('chat.beta', ['conversation' => $conversation->id]));

        $summaryResponse = $this->actingAs($recipient)->getJson(route('notifications.summary'));

        $summaryResponse
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('notifications.0.message_count', 2);

        $notification = $recipient->unreadNotifications()->latest()->first();

        $this->assertNotNull($notification);

        $this->actingAs($recipient)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect();

        $this->assertSame(0, $recipient->unreadNotifications()->count());
    }

    public function test_chat_messages_in_the_same_minute_only_show_the_time_on_the_last_message(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:15:00'));

        try {
            $sender = User::factory()->create([
                'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            ]);
            $recipient = User::factory()->create([
                'extra_role' => User::ROLE_HUMAN_RESOURCES,
            ]);

            $conversation = CompanyChatConversation::query()->create([
                'user_one_id' => min($sender->id, $recipient->id),
                'user_two_id' => max($sender->id, $recipient->id),
            ]);

            $this->actingAs($sender)
                ->post(route('chat.beta.messages.store', $conversation), [
                    'body' => 'Primer mensaje',
                ])
                ->assertRedirect(route('chat.beta', ['conversation' => $conversation->id]));

            $this->actingAs($sender)
                ->post(route('chat.beta.messages.store', $conversation), [
                    'body' => 'Segundo mensaje',
                ])
                ->assertRedirect(route('chat.beta', ['conversation' => $conversation->id]));

            $response = $this->actingAs($recipient)->getJson(route('chat.beta.messages.index', $conversation));

            $response
                ->assertOk()
                ->assertJsonPath('messages.0.show_time', false)
                ->assertJsonPath('messages.1.show_time', true);
        } finally {
            Carbon::setTestNow();
        }
    }
}
