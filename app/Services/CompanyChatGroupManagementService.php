<?php

namespace App\Services;

use App\Models\CompanyChatGroup;
use App\Models\CompanyChatGroupActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyChatGroupManagementService
{
    public function create(string $name, array $participantIds, User $actor): CompanyChatGroup
    {
        $participantIds = $this->normalizeParticipantIds($participantIds);
        $this->validateParticipantIds($participantIds->all());

        return DB::transaction(function () use ($name, $participantIds, $actor): CompanyChatGroup {
            $group = CompanyChatGroup::query()->create(['name' => $name]);
            $group->participants()->sync($participantIds);
            $group->load('participants');

            $this->storeActivityLog($actor, $group, CompanyChatGroupActivityLog::ACTION_CREATED, [
                'name_before' => null,
                'name_after' => $group->name,
                'participants_before' => [],
                'participants_after' => $this->participantNames($group),
            ]);

            return $group;
        });
    }

    public function update(CompanyChatGroup $group, string $name, array $participantIds, User $actor): CompanyChatGroup
    {
        $participantIds = $this->normalizeParticipantIds($participantIds);
        $this->validateParticipantIds($participantIds->all());
        $group->load('participants');
        $previousIds = $group->participants->pluck('id')->map(fn ($id): int => (int) $id);
        $addedIds = $participantIds->diff($previousIds)->values();
        $removedIds = $previousIds->diff($participantIds)->values();
        $changes = [];

        if ($group->name !== $name) {
            $changes['name_before'] = $group->name;
            $changes['name_after'] = $name;
        }

        if ($addedIds->isNotEmpty()) {
            $changes['participants_added'] = $this->participantNamesFromIds($addedIds->all());
        }

        if ($removedIds->isNotEmpty()) {
            $changes['participants_removed'] = $this->participantNamesFromIds($removedIds->all());
        }

        return DB::transaction(function () use ($group, $name, $participantIds, $actor, $changes, $addedIds, $removedIds): CompanyChatGroup {
            $group->update(['name' => $name]);
            $group->participants()->sync($participantIds->all());
            $group->load('participants');

            if ($changes !== []) {
                $this->storeActivityLog($actor, $group, CompanyChatGroupActivityLog::ACTION_UPDATED, $changes);
            }

            $systemMessages = app(CompanyChatGroupSystemMessageService::class);
            $members = $group->participants->keyBy('id');

            $addedIds->each(function (int $id) use ($systemMessages, $group, $members, $actor): void {
                if ($member = $members->get($id)) {
                    $systemMessages->recordParticipantAdded($group, $member, $actor);
                }
            });

            $removedIds->each(function (int $id) use ($systemMessages, $group, $actor): void {
                if ($member = User::query()->find($id)) {
                    $systemMessages->recordParticipantRemoved($group, $member, $actor);
                }
            });

            return $group;
        });
    }

    public function delete(CompanyChatGroup $group, User $actor): void
    {
        DB::transaction(function () use ($group, $actor): void {
            $this->storeActivityLog($actor, $group, CompanyChatGroupActivityLog::ACTION_DELETED, [
                'name_before' => $group->name,
                'name_after' => null,
                'participants_before' => $this->participantNames($group),
                'participants_after' => [],
            ]);

            $group->delete();
        });
    }

    public function normalizeParticipantIds(array $participantIds): \Illuminate\Support\Collection
    {
        return collect($participantIds)->map(fn ($id): int => (int) $id)->unique()->values();
    }

    private function validateParticipantIds(array $participantIds): void
    {
        if (count($participantIds) < 2) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Debes seleccionar al menos dos usuarios distintos.',
            ]);
        }

        $validCount = User::query()
            ->where('is_active', true)
            ->whereKey($participantIds)
            ->count();

        if ($validCount !== count($participantIds)) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Uno o más usuarios seleccionados no están disponibles.',
            ]);
        }
    }

    private function storeActivityLog(User $actor, CompanyChatGroup $group, string $action, array $changes): void
    {
        CompanyChatGroupActivityLog::query()->create([
            'action' => $action,
            'result' => 'success',
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'company_chat_group_id' => $group->id,
            'target_name' => $group->name,
            'changes' => $changes,
            'created_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 2000) ?: null,
        ]);
    }

    private function participantNames(CompanyChatGroup $group): array
    {
        $group->loadMissing('participants');

        return $this->participantNamesFromCollection($group->participants);
    }

    private function participantNamesFromIds(array $ids): array
    {
        return $this->participantNamesFromCollection(User::query()->whereKey($ids)->orderBy('name')->get());
    }

    private function participantNamesFromCollection(iterable $participants): array
    {
        return collect($participants)->pluck('name')->filter()->values()->all();
    }
}
