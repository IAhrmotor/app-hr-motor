<?php

namespace App\Console\Commands;

use App\Services\PurchaseLeaderboardService;
use App\Services\SalesforceLeaderboardService;
use App\Services\VehicleLeaderboardService;
use Illuminate\Console\Command;
use Throwable;

class SyncSalesforceLeaderboard extends Command
{
    protected $signature = 'salesforce:sync-leaderboard';

    protected $description = 'Sincroniza los leaderboards de ventas, compras y vehiculos desde Salesforce.';

    public function handle(
        SalesforceLeaderboardService $service,
        PurchaseLeaderboardService $purchaseService,
        VehicleLeaderboardService $vehicleService
    ): int
    {
        if (! $service->getConnection()) {
            $this->info('Sincronizacion omitida: Salesforce todavia no esta conectado.');

            return self::SUCCESS;
        }

        try {
            $salesEntries = $service->sync();
            $purchaseEntries = $purchaseService->sync();
            $vehicleEntries = $vehicleService->sync();
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Rankings sincronizados con %d ventas, %d compras y %d registros de vehiculos.',
            $salesEntries->count(),
            $purchaseEntries->count(),
            $vehicleEntries->count()
        ));

        return self::SUCCESS;
    }
}
