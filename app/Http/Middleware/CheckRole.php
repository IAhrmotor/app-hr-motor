<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && $this->isAdminLogRoute($routeName) && app_real_role($user) !== User::ROLE_ADMIN) {
            abort(403);
        }

        $currentRoles = app_effective_roles($user);

        if (array_intersect($currentRoles, $roles) === []) {
            if ($routeName && in_array(User::ROLE_ADMIN, $roles, true)) {
                if ($this->isAdminPermissionRoute($routeName)) {
                    $permissionKey = app_admin_permission_key_for_route($routeName);

                    if ($permissionKey && app_user_has_admin_permission($user, $permissionKey)) {
                        return $next($request);
                    }

                    if ($permissionKey === null && app_user_can_access_admin_panel($user)) {
                        return $next($request);
                    }
                }
            }

            if ($routeName && in_array(User::ROLE_MANAGER, $roles, true)) {
                if ($this->isAdminPermissionRoute($routeName)) {
                    $permissionKey = app_admin_permission_key_for_route($routeName);

                    if ($permissionKey && app_user_has_admin_permission($user, $permissionKey)) {
                        return $next($request);
                    }
                }
            }

            abort(403);
        }

        return $next($request);
    }

    private function isAdminPermissionRoute(string $routeName): bool
    {
        return Str::startsWith($routeName, [
            'users.',
            'dealerships.',
            'admin.contacts.',
            'admin.forum-tags.',
            'admin.magazine.',
            'admin.tablon.',
            'admin.notifications.',
            'admin.chat-retention-holds.',
            'admin.conversation-access.',
            'admin.chat-groups.',
            'admin.index',
        ]);
    }

    private function isAdminLogRoute(string $routeName): bool
    {
        return Str::startsWith($routeName, [
            'admin.logs.',
            'admin.content-logs.',
            'admin.bulletin-logs.',
            'admin.dealership-logs.',
            'admin.notification-logs.',
            'admin.policy-acceptance-logs.',
            'admin.chat-retention-logs.',
            'admin.conversation-access.logs.',
            'admin.chat-group-logs.',
            'admin.permission-logs.',
        ]);
    }
}
