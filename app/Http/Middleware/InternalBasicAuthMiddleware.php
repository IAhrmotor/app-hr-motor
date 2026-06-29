<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalBasicAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUser = (string) config('internal.google_reviews.user');
        $expectedPassword = (string) config('internal.google_reviews.password');

        if ($expectedUser === '' || $expectedPassword === '') {
            return $this->unauthorizedResponse();
        }

        $authorizationHeader = (string) $request->header('Authorization', '');

        if (! str_starts_with($authorizationHeader, 'Basic ')) {
            return $this->unauthorizedResponse();
        }

        $decodedCredentials = base64_decode(substr($authorizationHeader, 6), true);

        if ($decodedCredentials === false || ! str_contains($decodedCredentials, ':')) {
            return $this->unauthorizedResponse();
        }

        [$user, $password] = explode(':', $decodedCredentials, 2);

        if (! hash_equals($expectedUser, $user) || ! hash_equals($expectedPassword, $password)) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    private function unauthorizedResponse(): Response
    {
        return response()
            ->json(['message' => 'Unauthorized'], 401, [
                'WWW-Authenticate' => 'Basic realm="Internal Google Reviews"',
            ]);
    }
}
