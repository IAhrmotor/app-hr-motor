<?php

namespace App\Http\Controllers;

use App\Services\SalesforceLeaderboardService;
use Throwable;

class SalesforceLeaderboardSyncController extends Controller
{
    public function __invoke(SalesforceLeaderboardService $service)
    {
        if (! $service->getConnection()) {
            return redirect()
                ->route('leaderboard.index')
                ->with('error', 'Salesforce todavia no esta conectado. La app sigue operativa, pero el leaderboard no puede sincronizarse hasta completar la autorizacion.');
        }

        try {
            $service->sync();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('leaderboard.index')
                ->with('error', 'No se ha podido sincronizar el leaderboard con Salesforce. Revisa la conexion o la consulta configurada.');
        }

        return redirect()
            ->route('leaderboard.index')
            ->with('success', 'Leaderboard actualizado correctamente.');
    }
}
