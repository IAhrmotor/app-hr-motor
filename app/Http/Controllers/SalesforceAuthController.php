<?php

namespace App\Http\Controllers;

use App\Services\PurchaseLeaderboardService;
use App\Services\SalesforceLeaderboardService;
use App\Services\VehicleLeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SalesforceAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $redirectUri = config('services.salesforce.redirect_uri');

        if (blank(config('services.salesforce.client_id')) || blank($redirectUri)) {
            return redirect()
                ->route('leaderboard.sales')
                ->with('error', 'Faltan variables de entorno de Salesforce. Revisa SALESFORCE_CLIENT_ID y SALESFORCE_REDIRECT_URI.');
        }

        $state = Str::random(40);
        $request->session()->put('salesforce_oauth_state', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.salesforce.client_id'),
            'redirect_uri' => $redirectUri,
            'scope' => config('services.salesforce.scope'),
            'state' => $state,
        ]);

        return redirect()->away(config('services.salesforce.authorize_url') . '?' . $query);
    }

    public function callback(
        Request $request,
        SalesforceLeaderboardService $service,
        PurchaseLeaderboardService $purchaseService,
        VehicleLeaderboardService $vehicleService
    )
    {
        $expectedState = (string) $request->session()->pull('salesforce_oauth_state');
        $receivedState = (string) $request->string('state');

        abort_unless(
            filled($expectedState) && filled($receivedState) && hash_equals($expectedState, $receivedState),
            403
        );

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $service->saveAuthorizationCodeTokens($request->string('code')->toString());
        } catch (Throwable $exception) {
            Log::error('Salesforce OAuth callback failed.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('leaderboard.sales')
                ->with('error', 'No se ha podido completar la conexión OAuth con Salesforce.');
        }

        try {
            $service->sync();
            $purchaseService->sync();
            $vehicleService->sync();
        } catch (Throwable $exception) {
            Log::warning('Initial Salesforce leaderboard sync failed after OAuth.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('leaderboard.sales')
                ->with('error', 'Salesforce se ha conectado, pero el primer refresco de los rankings ha fallado. Revisa la SOQL configurada.');
        }

        return redirect()
            ->route('leaderboard.sales')
            ->with('success', 'Salesforce conectado correctamente y rankings sincronizados.');
    }
}
