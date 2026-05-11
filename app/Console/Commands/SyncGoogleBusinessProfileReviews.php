<?php

namespace App\Console\Commands;

use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Console\Command;
use Throwable;

class SyncGoogleBusinessProfileReviews extends Command
{
    protected $signature = 'google-business-profile:sync-reviews';

    protected $description = 'Sincroniza las reseñas de Google Business Profile.';

    public function handle(GoogleBusinessProfileReviewService $service): int
    {
        if (! $service->getConnection()) {
            $this->info('Sincronizacion omitida: Google Business Profile todavia no esta conectado.');

            return self::SUCCESS;
        }

        try {
            $syncedCount = $service->sync();
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Reseñas sincronizadas correctamente. Registros procesados: %d.', $syncedCount));

        return self::SUCCESS;
    }
}
