<?php

namespace Tests\Feature\Users;

use App\Models\CompanyChatGroup;
use App\Models\CompanyChatConversation;
use App\Models\Dealership;
use App\Models\User;
use App\Services\CompanyChatDefaultGroupSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserDefaultChatGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_added_to_default_extra_role_and_dealership_groups_on_create(): void
    {
        $dealership = Dealership::factory()->create(['name' => 'Madrid']);

        $user = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'dealership_id' => $dealership->id,
            'dealership' => $dealership->name,
        ]);

        $user->refresh()->load('chatGroups');

        $roleGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
            ->where('system_group_key', User::ROLE_INFORMATION_TECHNOLOGY)
            ->firstOrFail();

        $dealershipGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_DEALERSHIP)
            ->where('system_group_key', (string) $dealership->id)
            ->firstOrFail();

        $this->assertTrue($user->chatGroups->contains('id', $roleGroup->id));
        $this->assertTrue($user->chatGroups->contains('id', $dealershipGroup->id));
        $this->assertSame('Madrid', $dealershipGroup->name);
    }

    public function test_user_moves_between_dealership_groups_when_their_dealership_changes(): void
    {
        $firstDealership = Dealership::factory()->create(['name' => 'Torrejón']);
        $secondDealership = Dealership::factory()->create(['name' => 'Sevilla']);

        $user = User::factory()->create([
            'extra_role' => User::ROLE_COMMERCIAL,
            'dealership_id' => $firstDealership->id,
            'dealership' => $firstDealership->name,
        ]);

        $firstGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_DEALERSHIP)
            ->where('system_group_key', (string) $firstDealership->id)
            ->firstOrFail();

        $secondGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_DEALERSHIP)
            ->where('system_group_key', (string) $secondDealership->id)
            ->firstOrFail();

        $this->assertTrue($user->fresh()->chatGroups()->whereKey($firstGroup->id)->exists());
        $this->assertFalse($user->fresh()->chatGroups()->whereKey($secondGroup->id)->exists());

        $user->update([
            'dealership_id' => $secondDealership->id,
            'dealership' => $secondDealership->name,
        ]);

        $user->refresh();

        $this->assertFalse($user->chatGroups()->whereKey($firstGroup->id)->exists());
        $this->assertTrue($user->chatGroups()->whereKey($secondGroup->id)->exists());
    }

    public function test_user_moves_between_extra_role_groups_when_their_extra_role_changes(): void
    {
        $user = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'dealership_id' => null,
            'dealership' => null,
        ]);

        $firstGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
            ->where('system_group_key', User::ROLE_INFORMATION_TECHNOLOGY)
            ->firstOrFail();

        $secondGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
            ->where('system_group_key', User::ROLE_MARKETING)
            ->firstOrFail();

        $this->assertTrue($user->fresh()->chatGroups()->whereKey($firstGroup->id)->exists());
        $this->assertFalse($user->fresh()->chatGroups()->whereKey($secondGroup->id)->exists());

        $user->update([
            'extra_role' => User::ROLE_MARKETING,
        ]);

        $user->refresh();

        $this->assertFalse($user->chatGroups()->whereKey($firstGroup->id)->exists());
        $this->assertTrue($user->chatGroups()->whereKey($secondGroup->id)->exists());
    }

    public function test_changing_a_users_base_role_triggers_chat_sync(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'dealership_id' => null,
            'dealership' => null,
        ]);

        $syncService = $this->mock(CompanyChatDefaultGroupSyncService::class);
        $syncService->shouldReceive('syncUser')
            ->once()
            ->withArgs(function (User $syncedUser, bool $recordSystemMessages) use ($user): bool {
                return $syncedUser->is($user) && $recordSystemMessages === true;
            });

        $user->update([
            'role' => User::ROLE_MANAGER,
        ]);
    }

    public function test_changing_a_users_base_role_writes_system_messages_in_the_old_and_new_role_groups(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuario de pruebas',
            'role' => User::ROLE_USER,
            'extra_role' => null,
            'dealership_id' => null,
            'dealership' => null,
        ]);

        $oldGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_ROLE)
            ->where('system_group_key', User::ROLE_USER)
            ->firstOrFail();

        $newGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_ROLE)
            ->where('system_group_key', User::ROLE_MANAGER)
            ->firstOrFail();

        $this->assertTrue($user->fresh()->chatGroups()->whereKey($oldGroup->id)->exists());
        $this->assertFalse($user->fresh()->chatGroups()->whereKey($newGroup->id)->exists());

        $user->update([
            'role' => User::ROLE_MANAGER,
        ]);

        $user->refresh();

        $this->assertFalse($user->chatGroups()->whereKey($oldGroup->id)->exists());
        $this->assertTrue($user->chatGroups()->whereKey($newGroup->id)->exists());

        $removedConversation = CompanyChatConversation::query()
            ->where('company_chat_group_id', $oldGroup->id)
            ->firstOrFail();

        $addedConversation = CompanyChatConversation::query()
            ->where('company_chat_group_id', $newGroup->id)
            ->firstOrFail();

        $removedMessages = $removedConversation->messages()->orderBy('id')->get();
        $addedMessages = $addedConversation->messages()->orderBy('id')->get();

        $this->assertCount(1, $removedMessages);
        $this->assertCount(1, $addedMessages);
        $this->assertTrue($removedMessages->first()->is_system);
        $this->assertTrue($addedMessages->first()->is_system);
        $this->assertStringContainsString('salió', $removedMessages->first()->body);
        $this->assertStringContainsString('se ha unido', $addedMessages->first()->body);
        $this->assertStringContainsString($user->name, $removedMessages->first()->body);
        $this->assertStringContainsString($user->name, $addedMessages->first()->body);
    }

    public function test_changing_a_users_extra_role_writes_system_messages_in_the_old_and_new_groups(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuario de pruebas',
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'dealership_id' => null,
            'dealership' => null,
        ]);

        $firstGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
            ->where('system_group_key', User::ROLE_INFORMATION_TECHNOLOGY)
            ->firstOrFail();

        $secondGroup = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
            ->where('system_group_key', User::ROLE_MARKETING)
            ->firstOrFail();

        $user->update([
            'extra_role' => User::ROLE_MARKETING,
        ]);

        $removedConversation = CompanyChatConversation::query()
            ->where('company_chat_group_id', $firstGroup->id)
            ->firstOrFail();

        $addedConversation = CompanyChatConversation::query()
            ->where('company_chat_group_id', $secondGroup->id)
            ->firstOrFail();

        $removedMessages = $removedConversation->messages()->orderBy('id')->get();
        $addedMessages = $addedConversation->messages()->orderBy('id')->get();

        $this->assertCount(1, $removedMessages);
        $this->assertCount(1, $addedMessages);
        $this->assertTrue($removedMessages->first()->is_system);
        $this->assertTrue($addedMessages->first()->is_system);
        $this->assertStringContainsString('salió', $removedMessages->first()->body);
        $this->assertStringContainsString('se ha unido', $addedMessages->first()->body);
        $this->assertStringContainsString($user->name, $removedMessages->first()->body);
        $this->assertStringContainsString($user->name, $addedMessages->first()->body);
        $this->assertSame($removedMessages->first()->body, $removedConversation->fresh()->last_message_excerpt);
        $this->assertSame($addedMessages->first()->body, $addedConversation->fresh()->last_message_excerpt);
    }

    public function test_user_without_dealership_does_not_join_a_dealership_group(): void
    {
        $user = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'dealership_id' => null,
            'dealership' => null,
        ]);

        $user->refresh()->load('chatGroups');

        $this->assertFalse(
            $user->chatGroups->contains(function (CompanyChatGroup $group): bool {
                return $group->system_group_type === CompanyChatGroup::SYSTEM_GROUP_TYPE_DEALERSHIP;
            }),
        );
    }

    public function test_chat_sync_command_backfills_existing_users(): void
    {
        $dealership = Dealership::factory()->create(['name' => 'Barcelona']);
        $user = User::factory()->create([
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'dealership_id' => $dealership->id,
            'dealership' => $dealership->name,
        ]);

        $user->chatGroups()->detach();
        $this->assertCount(0, $user->fresh()->chatGroups()->get());

        Artisan::call('chat:sync-default-groups');

        $user->refresh();

        $this->assertGreaterThan(0, $user->chatGroups()->count());
        $this->assertTrue(
            $user->chatGroups()->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)->exists(),
        );
        $this->assertTrue(
            $user->chatGroups()->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_DEALERSHIP)->exists(),
        );
    }

    public function test_commercial_extra_role_does_not_create_a_default_group_and_cleans_existing_ones(): void
    {
        $dealership = Dealership::factory()->create(['name' => 'Madrid']);

        $user = User::factory()->create([
            'extra_role' => User::ROLE_COMMERCIAL,
            'dealership_id' => $dealership->id,
            'dealership' => $dealership->name,
        ]);

        $commercialGroup = CompanyChatGroup::query()->create([
            'name' => 'Comercial',
            'system_group_type' => CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE,
            'system_group_key' => User::ROLE_COMMERCIAL,
        ]);

        $user->chatGroups()->attach($commercialGroup->id);

        Artisan::call('chat:sync-default-groups');

        $user->refresh();

        $this->assertFalse(
            CompanyChatGroup::query()
                ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
                ->where('system_group_key', User::ROLE_COMMERCIAL)
                ->exists(),
        );
        $this->assertFalse($user->chatGroups()->whereKey($commercialGroup->id)->exists());
        $this->assertTrue(
            $user->chatGroups()->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_DEALERSHIP)->exists(),
        );
    }
}
