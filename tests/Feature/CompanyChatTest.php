<?php

namespace Tests\Feature;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertSame('Si, lo miro ahora mismo.', $conversation->last_message_excerpt);
        $this->assertNotNull($conversation->last_message_at);
        $this->assertDatabaseCount('company_chat_messages', 2);
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
            ->assertJsonPath('messages.0.sender_role_label', 'Informática');

        $message->refresh();

        $this->assertNotNull($message->read_at);
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

        CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Mensaje sin leer',
        ]);

        $response = $this->actingAs($recipient)->getJson(route('chat.beta.summary'));

        $response
            ->assertOk()
            ->assertJsonPath('conversations.0.partner_name', $sender->name)
            ->assertJsonPath('conversations.0.partner_role_label', 'Informática')
            ->assertJsonPath('conversations.0.unread_messages_count', 1)
            ->assertJsonPath('unread_messages_total', 1);
    }
}
