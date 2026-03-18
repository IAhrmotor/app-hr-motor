<?php

namespace App\Console\Commands;

use App\Services\PurchaseLeaderboardService;
use App\Services\SalesforceLeaderboardService;
use Illuminate\Console\Command;
use Throwable;

class SyncSalesforceLeaderboard extends Command
{
    protected $signature = 'salesforce:sync-leaderboard';

    protected $description = 'Sincroniza los leaderboards de ventas y compras desde Salesforce.';

    public function handle(SalesforceLeaderboardService $service, PurchaseLeaderboardService $purchaseService): int
    {
        if (! $service->getConnection()) {
            $this->info('Sincronizacion omitida: Salesforce todavia no esta conectado.');

            return self::SUCCESS;
        }

        try {
            $salesEntries = $service->sync();
            $purchaseEntries = $purchaseService->sync();
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Rankings sincronizados con %d ventas y %d compras.',
            $salesEntries->count(),
            $purchaseEntries->count()
        ));

        return self::SUCCESS;
    }
}
