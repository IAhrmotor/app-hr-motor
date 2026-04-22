<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $currentRoles = app_effective_roles($user);

        if (array_intersect($currentRoles, $roles) === []) {
            abort(403);
        }

        return $next($request);
    }
}
