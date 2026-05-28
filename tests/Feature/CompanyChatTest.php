<?php

namespace Tests\Feature;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\PolicyAcceptance;
use App\Models\User;
use App\Notifications\CompanyChatMessageNotification;
use App\Support\ChatPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CompanyChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        $attachmentsDirectory = storage_path('app/public/chat/attachments');

        if (File::exists($attachmentsDirectory)) {
            File::deleteDirectory($attachmentsDirectory);
        }

        parent::tearDown();
    }

    private function acceptChatPolicy(User $user, array $params = []): void
    {
        $this->actingAs($user)
            ->post(route('chat.beta.policy.accept', $params))
            ->assertRedirect(route('chat.beta'));
    }

    public function test_authenticated_user_can_open_the_chat_and_start_a_conversation_with_any_active_member(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create([
            'name' => 'Marta Test',
        ]);

        $this->acceptChatPolicy($sender);

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

        $this->acceptChatPolicy($recipient);

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

    public function test_user_can_send_chat_messages_with_attachments_and_without_text(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create([
            'name' => 'Marta Test',
        ]);

        $this->acceptChatPolicy($sender);
        $this->acceptChatPolicy($recipient);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $response = $this->actingAs($sender)
            ->post(route('chat.beta.messages.store', $conversation), [
                'body' => '',
                'attachments' => [
                    UploadedFile::fake()->image('captura-chat.png', 1200, 900),
                ],
            ]);

        $message = CompanyChatMessage::query()->latest('id')->first();

        $response
            ->assertRedirect(route('chat.beta', ['conversation' => $conversation->id]));

        $this->assertNotNull($message);
        $this->assertSame('', $message->body);
        $this->assertNotEmpty($message->attachments);
        $this->assertSame('Archivo adjunto: captura-chat.png', $conversation->fresh()->last_message_excerpt);
        $this->assertSame(1, count($message->attachments));
        $this->assertFileExists(storage_path('app/public/' . $message->attachments[0]['path']));

        $responseJson = $this->actingAs($recipient)->getJson(route('chat.beta.messages.index', $conversation));

        $responseJson
            ->assertOk()
            ->assertJsonPath('messages.0.preview_text', 'Archivo adjunto: captura-chat.png')
            ->assertJsonPath('messages.0.attachments.0.original_name', 'captura-chat.png')
            ->assertJsonPath('messages.0.attachments.0.is_image', true);
    }

    public function test_user_can_edit_and_delete_own_chat_messages(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create([
            'name' => 'Laura Test',
        ]);

        $this->acceptChatPolicy($sender);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $message = CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Mensaje original',
        ]);

        $recipient->notify(new CompanyChatMessageNotification($conversation, $message, $sender));

        $this->actingAs($sender)
            ->patchJson(route('chat.beta.messages.update', [$conversation, $message]), [
                'body' => 'Mensaje editado',
            ])
            ->assertOk()
            ->assertJsonPath('message.body', 'Mensaje editado')
            ->assertJsonPath('last_message_excerpt', 'Mensaje editado');

        $this->assertSame('Mensaje editado', $message->fresh()->body);
        $this->assertNotNull($message->fresh()->edited_at);
        $this->assertSame('Mensaje editado', $conversation->fresh()->last_message_excerpt);

        $this->actingAs($sender)
            ->deleteJson(route('chat.beta.messages.destroy', [$conversation, $message]))
            ->assertOk()
            ->assertJsonPath('conversation_id', $conversation->id)
            ->assertJsonPath('last_message_excerpt', 'Mensaje eliminado');

        $this->assertSoftDeleted('company_chat_messages', [
            'id' => $message->id,
        ]);
        $this->assertSame(0, $recipient->unreadNotifications()->count());
        $this->assertSame('Mensaje eliminado', $conversation->fresh()->last_message_excerpt);
    }

    public function test_chat_messages_endpoint_returns_json_and_marks_incoming_messages_as_read(): void
    {
        $sender = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
        ]);
        $recipient = User::factory()->create([
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
        ]);

        $this->acceptChatPolicy($recipient);

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
            ->assertJsonPath('partner_name', $sender->name)
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

        $this->acceptChatPolicy($sender);
        $this->acceptChatPolicy($recipient);

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

    public function test_chat_contact_can_be_marked_as_favorite_and_appears_in_the_summary(): void
    {
        $authUser = User::factory()->create();
        $favoriteContact = User::factory()->create([
            'name' => 'Marta Favorita',
        ]);

        $this->acceptChatPolicy($authUser);

        CompanyChatConversation::query()->create([
            'user_one_id' => min($authUser->id, $favoriteContact->id),
            'user_two_id' => max($authUser->id, $favoriteContact->id),
        ]);

        $this->actingAs($authUser)
            ->postJson(route('chat.beta.favorites.toggle', $favoriteContact))
            ->assertOk()
            ->assertJsonPath('is_favorite', true);

        $this->assertDatabaseHas('company_chat_favorite_contacts', [
            'user_id' => $authUser->id,
            'favorite_user_id' => $favoriteContact->id,
        ]);

        $summaryResponse = $this->actingAs($authUser)->getJson(route('chat.beta.summary'));

        $summaryResponse
            ->assertOk()
            ->assertJsonPath('favorite_contacts.0.name', $favoriteContact->name)
            ->assertJsonPath('conversations.0.partner_name', $favoriteContact->name)
            ->assertJsonPath('conversations.0.partner_is_favorite', true);
    }

    public function test_chat_live_search_returns_json_results_without_loading_the_full_chat_view(): void
    {
        $authUser = User::factory()->create();
        $matchedUser = User::factory()->create([
            'name' => 'Marta Búsqueda',
        ]);
        User::factory()->create([
            'name' => 'No debería salir',
        ]);

        $this->acceptChatPolicy($authUser);

        $response = $this->actingAs($authUser)->getJson(route('chat.beta', [
            'search' => 'Marta',
            'ajax' => 1,
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString($matchedUser->name, $response->json('html'));
        $this->assertStringNotContainsString('No debería salir', $response->json('html'));
    }

    public function test_chat_notifications_are_grouped_in_the_notifications_summary_and_marked_read_together(): void
    {
        $sender = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
        ]);
        $recipient = User::factory()->create([
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
        ]);

        $this->acceptChatPolicy($sender);
        $this->acceptChatPolicy($recipient);

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
            ->assertJsonPath('notifications.0.type', 'chat.message.received')
            ->assertJsonPath('notifications.0.actor_name', $sender->name)
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

            $this->acceptChatPolicy($sender);
            $this->acceptChatPolicy($recipient);

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

    public function test_chat_page_shows_policy_modal_until_the_user_accepts_the_current_version(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('chat.beta'));

        $response
            ->assertOk()
            ->assertSee('Política de uso del chat corporativo')
            ->assertSee('Aceptar y continuar');
    }

    public function test_chat_policy_acceptance_is_persisted_and_unlocks_the_chat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('chat.beta.policy.accept'), [
                'source' => 'ignored',
            ])
            ->assertRedirect(route('chat.beta'));

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'policy_version' => ChatPolicy::POLICY_VERSION,
            'source' => ChatPolicy::SOURCE_WEB_CHAT,
        ]);

        $this->actingAs($user)
            ->get(route('chat.beta'))
            ->assertOk()
            ->assertDontSee('Política de uso del chat corporativo');
    }

    public function test_chat_is_blocked_until_the_user_accepts_the_current_policy_version(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $this->actingAs($sender)
            ->post(route('chat.beta.messages.store', $conversation), [
                'body' => 'Mensaje bloqueado',
            ])
            ->assertForbidden();

        $this->actingAs($recipient)
            ->getJson(route('chat.beta.messages.index', $conversation))
            ->assertForbidden();

        $this->actingAs($recipient)
            ->getJson(route('chat.beta.summary'))
            ->assertForbidden();
    }

    public function test_chat_requires_acceptance_again_when_the_policy_version_changes(): void
    {
        $user = User::factory()->create();

        PolicyAcceptance::query()->create([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'policy_version' => '2026-01-01-v1',
            'accepted_at' => now()->subDay(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'source' => ChatPolicy::SOURCE_WEB_CHAT,
        ]);

        $this->actingAs($user)
            ->get(route('chat.beta'))
            ->assertOk()
            ->assertSee('Política de uso del chat corporativo');
    }
}
