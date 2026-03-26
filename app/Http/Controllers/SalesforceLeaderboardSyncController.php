<?php

namespace App\Http\Controllers;

use App\Services\PurchaseLeaderboardService;
use App\Services\SalesforceLeaderboardService;
use App\Services\VehicleLeaderboardService;
use Throwable;

class SalesforceLeaderboardSyncController extends Controller
{
    public function __invoke(
        SalesforceLeaderboardService $service,
        PurchaseLeaderboardService $purchaseService,
        VehicleLeaderboardService $vehicleService
    )
    {
        $redirect = redirect()->back();

        if (! $service->getConnection()) {
            return $redirect
                ->with('error', 'Salesforce todavia no esta conectado. La app sigue operativa, pero el leaderboard no puede sincronizarse hasta completar la autorizacion.');
        }

        try {
            $service->sync();
            $purchaseService->sync();
            $vehicleService->sync();
        } catch (Throwable $exception) {
            report($exception);

            return $redirect
                ->with('error', 'No se han podido sincronizar los rankings con Salesforce. Revisa la conexion o las consultas configuradas.');
        }

        return $redirect
            ->with('success', 'Rankings de ventas, compras y coches actualizados correctamente.');
    }
}
