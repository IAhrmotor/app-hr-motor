<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_activity_logs')
            ->select(['id', 'changes'])
            ->whereNotNull('changes')
            ->orderBy('id')
            ->chunkById(500, function ($logs): void {
                foreach ($logs as $log) {
                    $changes = json_decode((string) $log->changes, true);

                    if (! is_array($changes) || $changes === []) {
                        continue;
                    }

                    if (! array_key_exists('Día que entró en la empresa', $changes)) {
                        continue;
                    }

                    $change = $changes['Día que entró en la empresa'];
                    $from = $this->normalizeDateValue($change['from'] ?? null);
                    $to = $this->normalizeDateValue($change['to'] ?? null);

                    if ($from === null || $to === null || $from !== $to) {
                        continue;
                    }

                    unset($changes['Día que entró en la empresa']);

                    DB::table('user_activity_logs')
                        ->where('id', $log->id)
                        ->update([
                            'changes' => $changes === [] ? null : json_encode($changes, JSON_UNESCAPED_UNICODE),
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Irreversible data cleanup.
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
        } catch (\Throwable) {
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }
    }
};
