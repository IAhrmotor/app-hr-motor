<?php

namespace App\Console\Commands;

use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Console\Command;
use Throwable;

class DedupeGoogleBusinessProfileReviews extends Command
{
    protected $signature = 'google-business-profile:dedupe-reviews';

    protected $description = 'Elimina las reseñas duplicadas de Google Business Profile conservando la más reciente.';

    public function handle(GoogleBusinessProfileReviewService $service): int
    {
        try {
            $deletedCount = $service->dedupeDuplicateReviewRows();
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Limpieza completada. Filas duplicadas eliminadas: %d.', $deletedCount));

        return self::SUCCESS;
    }
}
