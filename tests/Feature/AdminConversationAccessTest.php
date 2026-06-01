<?php

namespace Tests\Feature;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConversationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_index_shows_the_new_conversation_access_sections_only_for_admins(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $manager = User::factory()->create([
            'role' => 'gestor',
            'name' => 'Gestor Principal',
            'email' => 'gestor@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee(route('admin.conversation-access.index'), false)
            ->assertSee(route('admin.conversation-access.logs.index'), false);

        $this->actingAs($manager)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee(route('admin.conversation-access.index'), false)
            ->assertDontSee(route('admin.conversation-access.logs.index'), false);
    }

    public function test_role_viewer_hides_conversation_access_sections_when_visible_role_is_gestor(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->withSession([
                'role_viewer.active_role' => 'gestor',
            ])
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee(route('admin.conversation-access.index'), false)
            ->assertDontSee(route('admin.conversation-access.logs.index'), false);
    }

    public function test_only_admins_can_open_the_conversation_access_logs_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $manager = User::factory()->create([
            'role' => 'gestor',
            'name' => 'Gestor Principal',
            'email' => 'gestor@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.conversation-access.logs.index'))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('admin.conversation-access.logs.index'))
            ->assertForbidden();
    }

    public function test_non_admin_attempt_to_open_another_users_conversation_is_denied_and_logged(): void
    {
        $manager = User::factory()->create([
            'role' => 'gestor',
            'name' => 'Gestor Principal',
            'email' => 'gestor@example.com',
        ]);
        $firstUser = User::factory()->create([
            'name' => 'Primer Usuario',
        ]);
        $secondUser = User::factory()->create([
            'name' => 'Segundo Usuario',
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($firstUser->id, $secondUser->id),
            'user_two_id' => max($firstUser->id, $secondUser->id),
        ]);

        $this->actingAs($manager)
            ->get(route('admin.conversation-access.index', [
                'conversation' => $conversation->id,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('company_chat_conversation_access_audits', [
            'company_chat_conversation_id' => $conversation->id,
            'admin_user_id' => $manager->id,
            'admin_email' => $manager->email,
            'action' => 'VIEW_CONVERSATION_AS_ADMIN_DENIED',
            'result' => 'denied',
        ]);
    }

    public function test_admin_must_register_a_reason_before_viewing_the_conversation_content(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $sender = User::factory()->create([
            'name' => 'Usuario Emisor',
            'email' => 'emisor@example.com',
        ]);
        $recipient = User::factory()->create([
            'name' => 'Usuario Receptor',
            'email' => 'receptor@example.com',
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Mensaje sensible de prueba',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.conversation-access.index', [
                'conversation' => $conversation->id,
            ]))
            ->assertOk()
            ->assertSee('Vas a acceder al contenido de una conversación en la que no participas.')
            ->assertDontSee('Mensaje sensible de prueba');

        $this->actingAs($admin)
            ->post(route('admin.conversation-access.store'), [
                'conversation_id' => $conversation->id,
                'reason' => 'Control interno justificado',
            ])
            ->assertRedirect(route('admin.conversation-access.index', [
                'conversation' => $conversation->id,
                'search' => '',
            ]));

        $this->assertDatabaseHas('company_chat_conversation_access_audits', [
            'company_chat_conversation_id' => $conversation->id,
            'admin_user_id' => $admin->id,
            'admin_email' => $admin->email,
            'action' => 'VIEW_CONVERSATION_AS_ADMIN',
            'reason' => 'Control interno justificado',
            'result' => 'granted',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.conversation-access.index', [
                'conversation' => $conversation->id,
            ]))
            ->assertOk()
            ->assertSee('Mensaje sensible de prueba');
    }

    public function test_granted_access_is_only_momentary_and_is_lost_on_new_navigation(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $sender = User::factory()->create([
            'name' => 'Usuario Emisor',
            'email' => 'emisor@example.com',
        ]);
        $recipient = User::factory()->create([
            'name' => 'Usuario Receptor',
            'email' => 'receptor@example.com',
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Mensaje efímero de prueba',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.conversation-access.store'), [
                'conversation_id' => $conversation->id,
                'reason' => 'Consulta puntual',
            ])
            ->assertRedirect(route('admin.conversation-access.index', [
                'conversation' => $conversation->id,
                'search' => '',
            ]));

        $this->actingAs($admin)
            ->get(route('admin.conversation-access.index', [
                'conversation' => $conversation->id,
            ]))
            ->assertOk()
            ->assertSee('Mensaje efímero de prueba');

        $this->actingAs($admin)
            ->get(route('admin.conversation-access.index', [
                'conversation' => $conversation->id,
            ]))
            ->assertOk()
            ->assertDontSee('Mensaje efímero de prueba')
            ->assertSee('Vas a acceder al contenido de una conversación en la que no participas.');
    }
}
