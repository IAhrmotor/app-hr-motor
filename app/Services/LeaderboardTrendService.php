<?php

namespace App\Services;

use App\Models\SalesLeaderboardDailySnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LeaderboardTrendService
{
    public function buildMovementMap(Collection $entries): array
    {
        if ($entries->isEmpty() || ! Schema::hasTable('sales_leaderboard_daily_snapshots')) {
            return $this->flatMovementMap($entries);
        }

        $yesterdaySnapshots = SalesLeaderboardDailySnapshot::query()
            ->whereDate('snapshot_date', today()->subDay())
            ->get();

        if ($yesterdaySnapshots->isEmpty()) {
            return $this->flatMovementMap($entries);
        }

        $snapshotMap = $yesterdaySnapshots->keyBy(fn (SalesLeaderboardDailySnapshot $snapshot) => $this->resolveComparableKey($snapshot));

        return $entries->mapWithKeys(function (Model $entry) use ($snapshotMap): array {
            $snapshot = $snapshotMap->get($this->resolveComparableKey($entry));

            if (! $snapshot) {
                return [$entry->getKey() => $this->makeMovementData(0)];
            }

            $delta = (int) $snapshot->ranking_position - (int) $entry->ranking_position;

            return [$entry->getKey() => $this->makeMovementData($delta)];
        })->all();
    }

    private function flatMovementMap(Collection $entries): array
    {
        return $entries->mapWithKeys(fn (Model $entry) => [$entry->getKey() => $this->makeMovementData(0)])->all();
    }

    private function makeMovementData(int $delta): array
    {
        if ($delta > 0) {
            return [
                'direction' => 'up',
                'amount' => $delta,
                'label' => "Sube {$delta} puestos",
            ];
        }

        if ($delta < 0) {
            return [
                'direction' => 'down',
                'amount' => abs($delta),
                'label' => 'Baja ' . abs($delta) . ' puestos',
            ];
        }

        return [
            'direction' => 'same',
            'amount' => 0,
            'label' => 'Se mantiene igual que ayer',
        ];
    }

    private function resolveComparableKey(Model $entry): string
    {
        $salesforceUserId = trim((string) ($entry->salesforce_user_id ?? ''));

        if ($salesforceUserId !== '') {
            return 'sf:' . $salesforceUserId;
        }

        $userId = $entry->user_id ?? null;

        if ($userId !== null) {
            return 'user:' . $userId;
        }

        return 'seller:' . Str::lower(trim((string) ($entry->seller_name ?? '')));
    }
}
