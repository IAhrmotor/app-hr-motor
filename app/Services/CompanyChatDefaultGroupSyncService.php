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
        $this->removeCommercialExtraRoleGroups();

        foreach ($this->managedBaseRoles() as $baseRole) {
            $this->ensureBaseRoleGroup($baseRole);
        }

        foreach ($this->managedExtraRoles() as $extraRole) {
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

            $currentGroups = $user->chatGroups()->get();

            if (! $user->is_active || $user->isDisabled()) {
                if ($currentGroups->isNotEmpty()) {
                    $user->chatGroups()->detach($currentGroups->pluck('id')->all());
                }

                return;
            }

            $currentManagedGroups = $currentGroups
                ->whereNotNull('system_group_type')
                ->keyBy('id');

            $desiredGroupIds = collect()
                ->merge($this->desiredChatRoleGroupId($user))
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
        if (! $this->isManagedExtraRole($extraRole)) {
            throw new \InvalidArgumentException('El grupo automático solicitado no está gestionado por chat.');
        }

        $groupName = (string) (User::extraRoleLabels()[$extraRole] ?? ucfirst($extraRole));

        return $this->ensureSystemGroup(
            type: CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE,
            key: $extraRole,
            name: $groupName,
        );
    }

    public function ensureBaseRoleGroup(string $baseRole): CompanyChatGroup
    {
        if (! $this->isManagedBaseRole($baseRole)) {
            throw new \InvalidArgumentException('El grupo automatico solicitado no esta gestionado por chat.');
        }

        $groupName = (string) (User::baseRoleLabels()[$baseRole] ?? ucfirst($baseRole));

        return $this->ensureSystemGroup(
            type: CompanyChatGroup::SYSTEM_GROUP_TYPE_ROLE,
            key: $baseRole,
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

    private function desiredChatRoleGroupId(User $user): Collection
    {
        if (filled($user->extra_role)) {
            if (! $this->isManagedExtraRole($user->extra_role)) {
                return collect();
            }

            return collect([$this->ensureExtraRoleGroup($user->extra_role)->id]);
        }

        if ($user->role === User::ROLE_HR_NEWCARS) {
            return collect([$this->ensureExtraRoleGroup(User::ROLE_HR_NEWCARS)->id]);
        }

        if (blank($user->role) || ! $this->isManagedBaseRole($user->role)) {
            return collect();
        }

        return collect([$this->ensureBaseRoleGroup($user->role)->id]);
    }

    /**
     * @return array<int, string>
     */
    private function managedExtraRoles(): array
    {
        return array_values(array_filter(
            array_keys(User::extraRoleLabels()),
            fn (string $extraRole): bool => $this->isManagedExtraRole($extraRole),
        ));
    }

    private function isManagedExtraRole(string $extraRole): bool
    {
        return $extraRole !== User::ROLE_COMMERCIAL;
    }

    /**
     * @return array<int, string>
     */
    private function managedBaseRoles(): array
    {
        return array_keys(User::baseRoleLabels());
    }

    private function isManagedBaseRole(string $baseRole): bool
    {
        return array_key_exists($baseRole, User::baseRoleLabels());
    }

    private function removeCommercialExtraRoleGroups(): void
    {
        $groups = CompanyChatGroup::query()
            ->where('system_group_type', CompanyChatGroup::SYSTEM_GROUP_TYPE_EXTRA_ROLE)
            ->where('system_group_key', User::ROLE_COMMERCIAL)
            ->get();

        if ($groups->isEmpty()) {
            return;
        }

        $groups->each(function (CompanyChatGroup $group): void {
            $group->participants()->detach();
            $group->delete();
        });
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
