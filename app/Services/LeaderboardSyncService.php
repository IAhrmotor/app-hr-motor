<?php

namespace App\Services;

use Throwable;

class LeaderboardSyncService
{
    public function __construct(
        private readonly SalesforceLeaderboardService $salesforceService,
        private readonly PurchaseLeaderboardService $purchaseService,
        private readonly VehicleLeaderboardService $vehicleService,
    ) {
    }

    public function hasSalesforceConnection(): bool
    {
        return $this->salesforceService->getConnection() !== null;
    }

    /**
     * Runs the same three ranking synchronisations used by the legacy admin button.
     *
     * @throws Throwable
     */
    public function sync(): void
    {
        $this->salesforceService->sync();
        $this->purchaseService->sync();
        $this->vehicleService->sync();
    }
}
