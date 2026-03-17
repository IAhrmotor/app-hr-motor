<?php

namespace App\Console\Commands;

use App\Services\SalesforceLeaderboardService;
use Illuminate\Console\Command;
use Throwable;

class SyncSalesforceLeaderboard extends Command
{
    protected $signature = 'salesforce:sync-leaderboard';

    protected $description = 'Sincroniza el leaderboard de ventas desde Salesforce.';

    public function handle(SalesforceLeaderboardService $service): int
    {
        if (! $service->getConnection()) {
            $this->info('Sincronizacion omitida: Salesforce todavia no esta conectado.');

            return self::SUCCESS;
        }

        try {
            $entries = $service->sync();
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Leaderboard sincronizado con %d registros.', $entries->count()));

        return self::SUCCESS;
    }
}
