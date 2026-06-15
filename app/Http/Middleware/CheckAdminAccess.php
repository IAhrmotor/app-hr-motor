<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (app_real_role($user) === User::ROLE_ADMIN) {
            return $next($request);
        }

        if ($permissions === []) {
            if (app_user_can_access_admin_panel($user)) {
                return $next($request);
            }

            abort(403);
        }

        foreach ($permissions as $permissionKey) {
            if (app_user_has_admin_permission($user, $permissionKey)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
