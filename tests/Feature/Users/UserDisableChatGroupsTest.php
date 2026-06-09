<?php

namespace Tests\Feature\Users;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatGroup;
use App\Models\CompanyChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDisableChatGroupsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_disabling_a_user_removes_them_from_chat_groups_but_keeps_chat_history(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Usuario desactivado',
            'is_active' => true,
        ]);
        $other = User::factory()->create([
            'is_active' => true,
        ]);

        $group = CompanyChatGroup::query()->create([
            'name' => 'Grupo de prueba',
        ]);
        $group->participants()->sync([$member->id, $other->id]);

        $groupConversation = CompanyChatConversation::query()->create([
            'company_chat_group_id' => $group->id,
        ]);
        CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $groupConversation->id,
            'sender_id' => $member->id,
            'body' => 'Mensaje en grupo',
        ]);

        $directConversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($admin->id, $member->id),
            'user_two_id' => max($admin->id, $member->id),
        ]);
        CompanyChatMessage::query()->create([
            'company_chat_conversation_id' => $directConversation->id,
            'sender_id' => $member->id,
            'body' => 'Mensaje directo',
        ]);

        $this->actingAs($admin)
            ->patch(route('users.disable', $member), [
                'disabled_reason' => 'Baja de prueba',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertFalse($member->fresh()->is_active);
        $this->assertDatabaseMissing('company_chat_group_user', [
            'company_chat_group_id' => $group->id,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseHas('company_chat_messages', [
            'company_chat_conversation_id' => $groupConversation->id,
            'sender_id' => $member->id,
            'body' => 'Mensaje en grupo',
        ]);
        $this->assertDatabaseHas('company_chat_messages', [
            'company_chat_conversation_id' => $directConversation->id,
            'sender_id' => $member->id,
            'body' => 'Mensaje directo',
        ]);
        $this->assertTrue($groupConversation->fresh()->messages()->exists());
        $this->assertTrue($directConversation->fresh()->messages()->exists());
    }
}
