<?php

namespace App\Http\Controllers;

use App\Exceptions\CommercialCommissionsApiUnavailableException;
use App\Exceptions\CommercialCommissionsNetworkException;
use App\Exceptions\CommercialCommissionsRateLimitException;
use App\Exceptions\CommercialNotFoundException;
use App\Exceptions\InvalidCommercialCommissionParametersException;
use App\Exceptions\InvalidCommercialCommissionsResponseException;
use App\Exceptions\MissingSalesforceUserIdException;
use App\Services\CommercialCommissionsApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommercialCommissionsController extends Controller
{
    public function showOwn(Request $request, CommercialCommissionsApiService $service): JsonResponse
    {
        return $this->showCommission($request, $service);
    }

    private function showCommission(Request $request, CommercialCommissionsApiService $service): JsonResponse
    {
        $user = $request->user();

        Gate::forUser($user)->authorize('commercial-commission.view');

        $month = (string) $request->query('month', now()->format('Y-m'));

        try {
            return response()->json([
                'data' => $service->get($user, $month),
            ]);
        } catch (MissingSalesforceUserIdException|CommercialNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        } catch (InvalidCommercialCommissionParametersException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (CommercialCommissionsRateLimitException $exception) {
            $response = response()->json(['message' => $exception->getMessage()], 429);

            if ($exception->retryAfter !== null) {
                $response->header('Retry-After', (string) $exception->retryAfter);
            }

            return $response;
        } catch (CommercialCommissionsApiUnavailableException|CommercialCommissionsNetworkException|InvalidCommercialCommissionsResponseException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }
}
