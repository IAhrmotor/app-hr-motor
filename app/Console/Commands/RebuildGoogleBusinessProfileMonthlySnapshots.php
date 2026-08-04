<?php

namespace App\Console\Commands;

use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Console\Command;
use Throwable;

class RebuildGoogleBusinessProfileMonthlySnapshots extends Command
{
    protected $signature = 'google-business-profile:rebuild-monthly-snapshots {month? : Mes en formato YYYY-MM. Si se omite, reconstruye todos los meses disponibles}';

    protected $description = 'Reconstruye los snapshots mensuales de reseñas de Google Business Profile.';

    public function handle(GoogleBusinessProfileReviewService $service): int
    {
        $month = trim((string) $this->argument('month'));
        $month = $month !== '' ? $month : null;

        if ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $this->error('El mes debe tener el formato YYYY-MM.');

            return self::FAILURE;
        }

        try {
            $upsertedCount = $service->rebuildMonthlySnapshots($month);
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($month) {
            $this->info(sprintf('Snapshots mensuales reconstruidos para %s. Registros actualizados: %d.', $month, $upsertedCount));
        } else {
            $this->info(sprintf('Snapshots mensuales reconstruidos. Registros actualizados: %d.', $upsertedCount));
        }

        return self::SUCCESS;
    }
}
