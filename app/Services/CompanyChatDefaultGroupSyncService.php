<?php

namespace App\Services;

use App\Models\CompanyChatGroup;
use App\Models\Dealership;
use App\Models\User;
use App\Services\CompanyChatGroupSystemMessageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyChatDefaultGroupSyncService
{
    public function ensureDefaultGroupsExist(): void
    {
        foreach (array_keys(User::extraRoleLabels()) as $extraRole) {
            $this->ensureExtraRoleGroup($extraRole);
        }

        Dealership::query()->orderBy('id')->chunkById(100, function ($dealerships): void {
            foreach ($dealerships as $dealership) {
                $this->ensureDealershipGroup($dealership);
            }
        });
    }

    public function syncUser(User $user, bool $recordSystemMessages = false): void
    {
        DB::transaction(function () use ($user, $recordSystemMessages): void {
            $systemMessageService = app(CompanyChatGroupSystemMessageService::class);

            $currentManagedGroups = CompanyChatGroup::query()
                ->whereNotNull('system_group_type')
                ->whereHas('participants', function ($query) use ($user): void {
                    $query->whereKey($user->id);
                })
                ->get()
                ->keyBy('id');

            $desiredGroupIds = collect()
                ->merge($this->desiredExtraRoleGroupId($user))
                ->merge($this->desiredDealershipGroupId($user))
                ->filter()
                ->unique()
                ->values();

            $managedGroupIds = $currentManagedGroups->keys()->values();

            $toDetach = $managedGroupIds->diff($desiredGroupIds)->values();
            $toAttach = $desiredGroupIds->diff($managedGroupIds)->values();

            if ($toDetach->isNotEmpty()) {
                $user->chatGroups()->detach($toDetach->all());
            }

            if ($desiredGroupIds->isNotEmpty()) {
                $user->chatGroups()->syncWithoutDetaching($desiredGroupIds->all());
            }

            if ($recordSystemMessages) {
                $currentManagedGroups->only($toDetach->all())
                    ->each(function (CompanyChatGroup $group) use ($systemMessageService, $user): void {
                        $systemMessageService->recordParticipantRemoved($group, $user);
                    });

                $toAttach->each(function (int $groupId) use ($systemMessageService, $user): void {
                    $group = CompanyChatGroup::query()->find($groupId);

                    if ($group) {
                        $systemMessageService->recordParticipantAdded($group, $user);
                    }
                });
            }
        });
    }

    public function syncDealership(Dealership $dealership): ?CompanyChatGroup
    {
        if (! $dealership->exists) {
            return null;
        }

        return $this->ensureDealershipGroup($dealership);
    }

    public function ensureExtraRoleGroup(string $extraRole): CompanyChatGroup
    {
        $groupName = (string) (User::extraRoleLabels()[$extraRole] ?? ucfirst($extraRole));

        return $this->ensureSystemGroup(
            type: CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE,
            key: $extraRole,
            name: $groupName,
        );
    }

    public function ensureDealershipGroup(Dealership $dealership): ?CompanyChatGroup
    {
        if (! $dealership->exists) {
            return null;
        }

        return $this->ensureSystemGroup(
            type: CompanyChatGroup::SYSTEM_GROUP_TYPE_DEALERSHIP,
            key: (string) $dealership->id,
            name: $dealership->name,
        );
    }

    private function ensureSystemGroup(string $type, string $key, string $name): CompanyChatGroup
    {
        $group = CompanyChatGroup::query()
            ->where('system_group_type', $type)
            ->where('system_group_key', $key)
            ->first();

        if (! $group) {
            $group = CompanyChatGroup::query()
                ->where('name', $name)
                ->first();
        }

        if (! $group) {
            $group = new CompanyChatGroup();
        }

        if (
            $group->name !== $name
            || $group->system_group_type !== $type
            || $group->system_group_key !== $key
        ) {
            $group->forceFill([
                'name' => $name,
                'system_group_type' => $type,
                'system_group_key' => $key,
            ])->save();
        }

        return $group;
    }

    private function desiredExtraRoleGroupId(User $user): Collection
    {
        if (blank($user->extra_role)) {
            return collect();
        }

        return collect([$this->ensureExtraRoleGroup($user->extra_role)->id]);
    }

    private function desiredDealershipGroupId(User $user): Collection
    {
        if (! $user->dealership_id) {
            return collect();
        }

        $dealership = Dealership::query()->find($user->dealership_id);

        if (! $dealership) {
            return collect();
        }

        $group = $this->ensureDealershipGroup($dealership);

        return collect($group?->id ? [$group->id] : []);
    }
}
