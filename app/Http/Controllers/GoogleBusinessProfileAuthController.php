<?php

namespace App\Http\Controllers;

use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GoogleBusinessProfileAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $redirectUri = config('services.google_business_profile.redirect_uri');

        if (blank(config('services.google_business_profile.client_id')) || blank($redirectUri)) {
            return redirect()
                ->route('reviews.index')
                ->with('error', 'Faltan variables de entorno de Google Business Profile. Revisa GOOGLE_BUSINESS_PROFILE_CLIENT_ID y GOOGLE_BUSINESS_PROFILE_REDIRECT_URI.');
        }

        $state = Str::random(40);
        $request->session()->put('google_business_profile_oauth_state', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.google_business_profile.client_id'),
            'redirect_uri' => $redirectUri,
            'scope' => config('services.google_business_profile.scope'),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect()->away(config('services.google_business_profile.authorize_url') . '?' . $query);
    }

    public function callback(Request $request, GoogleBusinessProfileReviewService $service)
    {
        $expectedState = (string) $request->session()->pull('google_business_profile_oauth_state');
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
            $service->sync();
        } catch (Throwable $exception) {
            Log::error('Google Business Profile OAuth callback failed.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('reviews.index')
                ->with('error', 'No se ha podido completar la conexion OAuth con Google Business Profile.');
        }

        return redirect()
            ->route('reviews.index')
            ->with('success', 'Google Business Profile conectado correctamente y reseñas sincronizadas.');
    }
}
