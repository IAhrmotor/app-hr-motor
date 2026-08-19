<?php

namespace App\Services;

use App\Models\Dealership;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneActivityLog;

class ZoneManagementService
{
    public function allDealershipOptions(): array
    {
        return Dealership::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function availableDealershipOptions(?Zone $zone = null): array
    {
        return Dealership::query()
            ->with('zone')
            ->where(function ($query) use ($zone): void {
                $query->whereNull('zone_id');

                if ($zone) {
                    $query->orWhere('zone_id', $zone->id);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function isDealershipAssignedElsewhere(int $dealershipId, ?int $zoneId = null): bool
    {
        return Dealership::query()
            ->whereKey($dealershipId)
            ->when($zoneId, fn ($query) => $query->where('zone_id', '!=', $zoneId))
            ->whereNotNull('zone_id')
            ->exists();
    }

    public function conflictingDealershipNames(array $dealershipIds, ?Zone $zone = null): array
    {
        if ($dealershipIds === []) {
            return [];
        }

        return Dealership::query()
            ->with('zone')
            ->whereIn('id', $dealershipIds)
            ->whereNotNull('zone_id')
            ->when($zone, fn ($query) => $query->where('zone_id', '!=', $zone->id))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function dealershipNames(array $dealershipIds): array
    {
        if ($dealershipIds === []) {
            return [];
        }

        return Dealership::query()
            ->whereIn('id', $dealershipIds)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function syncDealershipAssignments(Zone $zone, array $dealershipIds): void
    {
        $zone->loadMissing('dealerships');

        $previousDealershipIds = $zone->dealerships
            ->pluck('id')
            ->map(fn ($value) => (int) $value)
            ->values();

        $normalizedDealershipIds = collect($dealershipIds)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $removedDealershipIds = $previousDealershipIds->diff($normalizedDealershipIds)->values();
        $addedDealershipIds = $normalizedDealershipIds->diff($previousDealershipIds)->values();

        if ($removedDealershipIds->isNotEmpty()) {
            Dealership::query()
                ->whereIn('id', $removedDealershipIds->all())
                ->update(['zone_id' => null]);
        }

        if ($addedDealershipIds->isNotEmpty()) {
            Dealership::query()
                ->whereIn('id', $addedDealershipIds->all())
                ->update(['zone_id' => $zone->id]);
        }
    }

    public function recordActivityLog(
        User $actor,
        Zone $zone,
        string $action,
        array $changes = [],
        array $dealershipNames = [],
    ): void {
        ZoneActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_zone_id' => $zone->id,
            'target_name' => $zone->name,
            'target_dealerships' => $dealershipNames,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
        ]);
    }
}
