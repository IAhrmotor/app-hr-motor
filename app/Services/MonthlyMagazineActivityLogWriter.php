<?php

namespace App\Services;

use App\Models\MonthlyMagazineActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class MonthlyMagazineActivityLogWriter
{
    public function record(
        ?User $actor,
        string $action,
        string $targetName,
        ?string $targetReference = null,
        array $changes = [],
    ): void {
        if (! Schema::hasTable('monthly_magazine_activity_logs')) {
            return;
        }

        $changes = self::normalizeChanges($changes);

        if ($changes === []) {
            return;
        }

        MonthlyMagazineActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'Sistema',
            'actor_email' => $actor?->email,
            'target_name' => $targetName,
            'target_reference' => $targetReference,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, array{from?: mixed, to?: mixed}>  $changes
     * @return array<string, array{from?: mixed, to?: mixed}>
     */
    public static function normalizeChanges(array $changes): array
    {
        return array_filter(
            $changes,
            function (array $change): bool {
                $from = $change['from'] ?? null;
                $to = $change['to'] ?? null;

                if (blank($from) && blank($to)) {
                    return false;
                }

                return $from !== $to;
            },
        );
    }

    public function cleanupHistoricalRecords(): int
    {
        if (! Schema::hasTable('monthly_magazine_activity_logs')) {
            return 0;
        }

        $processed = 0;

        MonthlyMagazineActivityLog::query()
            ->orderBy('id')
            ->chunkById(100, function ($logs) use (&$processed): void {
                foreach ($logs as $log) {
                    $processed++;
                    $normalizedChanges = self::normalizeChanges((array) ($log->changes ?? []));

                    if ($normalizedChanges === []) {
                        $log->delete();

                        continue;
                    }

                    $log->changes = $normalizedChanges === [] ? null : $normalizedChanges;
                    $log->save();
                }
            });

        return $processed;
    }
}
