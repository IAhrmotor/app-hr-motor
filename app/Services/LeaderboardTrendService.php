<?php

namespace App\Services;

use App\Models\PurchaseLeaderboardDailySnapshot;
use App\Models\SalesLeaderboardDailySnapshot;
use App\Models\VehicleLeaderboardDailySnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LeaderboardTrendService
{
    public function buildMovementMap(
        Collection $entries,
        string $snapshotModelClass = SalesLeaderboardDailySnapshot::class,
        ?string $snapshotTable = null
    ): array
    {
        $snapshotTable ??= $snapshotModelClass === PurchaseLeaderboardDailySnapshot::class
            ? 'purchase_leaderboard_daily_snapshots'
            : ($snapshotModelClass === VehicleLeaderboardDailySnapshot::class
                ? 'vehicle_leaderboard_daily_snapshots'
                : 'sales_leaderboard_daily_snapshots');

        if ($entries->isEmpty() || ! Schema::hasTable($snapshotTable)) {
            return $this->flatMovementMap($entries);
        }

        $yesterdaySnapshots = $snapshotModelClass::query()
            ->whereDate('snapshot_date', today()->subDay())
            ->when(
                $entries->first()?->getAttribute('temperature'),
                fn ($query, string $temperature) => $query->where('temperature', $temperature)
            )
            ->get();

        if ($yesterdaySnapshots->isEmpty()) {
            return $this->flatMovementMap($entries);
        }

        $snapshotMap = $yesterdaySnapshots->keyBy(fn (Model $snapshot) => $this->resolveComparableKey($snapshot));

        return $entries->mapWithKeys(function (Model $entry) use ($snapshotMap): array {
            $snapshot = $snapshotMap->get($this->resolveComparableKey($entry));

            if (! $snapshot) {
                return [$entry->getKey() => $this->makeMovementData(0)];
            }

            $delta = (int) $snapshot->ranking_position - (int) $entry->ranking_position;

            return [$entry->getKey() => $this->makeMovementData($delta)];
        })->all();
    }

    public function buildDealershipMovementMap(
        Collection $entries,
        string $snapshotModelClass = SalesLeaderboardDailySnapshot::class,
        ?string $snapshotTable = null,
        string $metricField = 'total_sales'
    ): array
    {
        $snapshotTable ??= $snapshotModelClass === PurchaseLeaderboardDailySnapshot::class
            ? 'purchase_leaderboard_daily_snapshots'
            : ($snapshotModelClass === VehicleLeaderboardDailySnapshot::class
                ? 'vehicle_leaderboard_daily_snapshots'
                : 'sales_leaderboard_daily_snapshots');

        if ($entries->isEmpty() || ! Schema::hasTable($snapshotTable)) {
            return $this->flatMovementMap($entries);
        }

        $yesterdaySnapshots = $snapshotModelClass::query()
            ->whereDate('snapshot_date', today()->subDay())
            ->get();

        if ($yesterdaySnapshots->isEmpty()) {
            return $this->flatMovementMap($entries);
        }

        $userDealerships = User::query()
            ->select('id', 'dealership')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $this->normalizeDealership($user->dealership)])
            ->all();

        $rankedDealerships = $yesterdaySnapshots
            ->groupBy(function (Model $snapshot) use ($userDealerships): string {
                return $userDealerships[$snapshot->user_id] ?? $this->normalizeDealership(null);
            })
            ->map(function (Collection $dealershipSnapshots, string $dealership) use ($metricField): array {
                return [
                    'dealership' => $dealership,
                    'total' => (float) $dealershipSnapshots->sum($metricField),
                ];
            })
            ->sort(function (array $left, array $right) {
                $metricComparison = $right['total'] <=> $left['total'];

                if ($metricComparison !== 0) {
                    return $metricComparison;
                }

                return strcmp($left['dealership'], $right['dealership']);
            })
            ->values()
            ->map(fn (array $row, int $index): array => $row + ['ranking_position' => $index + 1]);

        $snapshotMap = collect($rankedDealerships)
            ->mapWithKeys(fn (array $row): array => [$this->dealershipKey($row['dealership']) => $row]);

        return $entries->mapWithKeys(function (Model $entry) use ($snapshotMap): array {
            $dealership = $this->normalizeDealership($entry->getAttribute('dealership_name'));
            $snapshot = $snapshotMap->get($this->dealershipKey($dealership));

            if (! $snapshot) {
                return [$entry->getKey() => $this->makeMovementData(0)];
            }

            $delta = (int) $snapshot['ranking_position'] - (int) $entry->ranking_position;

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

        $vehicleSalesforceId = trim((string) ($entry->vehicle_salesforce_id ?? ''));

        if ($vehicleSalesforceId !== '') {
            return 'vehicle:' . $vehicleSalesforceId;
        }

        $userId = $entry->user_id ?? null;

        if ($userId !== null) {
            return 'user:' . $userId;
        }

        $vehicleName = trim((string) ($entry->vehicle_name ?? ''));

        if ($vehicleName !== '') {
            return 'vehicle-name:' . Str::lower($vehicleName);
        }

        return 'seller:' . Str::lower(trim((string) ($entry->seller_name ?? '')));
    }

    private function normalizeDealership(?string $dealership): string
    {
        $dealership = trim((string) $dealership);

        return $dealership !== '' ? $dealership : 'Sin delegacion asignada';
    }

    private function dealershipKey(string $dealership): string
    {
        return 'dealership:' . Str::lower($dealership);
    }
}
