<?php

use App\Models\AdminPermissionGrant;
use App\Models\User;
use Illuminate\Support\Str;

if (! function_exists('app_effective_roles')) {
    function app_effective_roles(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        if (app_role_viewer_enabled($user)) {
            $viewerRole = session('role_viewer.active_role');
            $allowedRoles = array_keys(app_role_viewer_options($user));

            if (is_string($viewerRole) && $viewerRole !== $user->role && in_array($viewerRole, $allowedRoles, true)) {
                return [$viewerRole];
            }
        }

        return array_values(array_unique(array_filter([
            $user->role,
            $user->extra_role,
        ])));
    }
}

if (! function_exists('app_user_has_any_role')) {
    function app_user_has_any_role(?User $user, array $roles): bool
    {
        return $user !== null && array_intersect(app_effective_roles($user), $roles) !== [];
    }
}

if (! function_exists('app_can_access_videos')) {
    function app_can_access_videos(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $allowedRoles = [
            User::ROLE_COMMERCIAL,
            User::ROLE_STORE_MANAGER,
            User::ROLE_AREA_MANAGER,
        ];

        if ($user->role === User::ROLE_ADMIN) {
            return app_role_viewer_active($user) && in_array(app_visible_role($user), $allowedRoles, true);
        }

        return app_user_has_any_role($user, $allowedRoles);
    }
}

if (! function_exists('app_can_access_rankings')) {
    function app_can_access_rankings(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $allowedRoles = [
            User::ROLE_COMMERCIAL,
            User::ROLE_STORE_MANAGER,
            User::ROLE_AREA_MANAGER,
            User::ROLE_HR_NEWCARS,
            User::ROLE_MANAGEMENT,
        ];

        if ($user->role === User::ROLE_ADMIN) {
            return app_role_viewer_active($user) && in_array(app_visible_role($user), $allowedRoles, true);
        }

        return app_user_has_any_role($user, $allowedRoles);
    }
}

if (! function_exists('app_can_open_rankings_pages')) {
    function app_can_open_rankings_pages(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return app_can_access_rankings($user);
    }
}

if (! function_exists('app_can_access_web')) {
    function app_can_access_web(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $allowedRoles = [
            User::ROLE_ADMIN,
            User::ROLE_COMMERCIAL,
            User::ROLE_STORE_MANAGER,
            User::ROLE_CALL_CENTER,
            User::ROLE_INFORMATION_TECHNOLOGY,
            User::ROLE_MARKETING,
            User::ROLE_AREA_MANAGER,
            User::ROLE_MANAGEMENT,
        ];

        if ($user->role === User::ROLE_ADMIN) {
            return ! app_role_viewer_active($user) || in_array(app_visible_role($user), $allowedRoles, true);
        }

        return app_user_has_any_role($user, $allowedRoles);
    }
}

if (! function_exists('app_can_access_chat_beta')) {
    function app_can_access_chat_beta(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return (bool) $user->is_active && ! $user->isDisabled();
    }
}

if (! function_exists('app_chat_role_label')) {
    function app_chat_role_label(?User $user = null): string
    {
        $user ??= auth()->user();

        if (! $user) {
            return 'Usuario';
        }

        return $user->chat_role_label;
    }
}

if (! function_exists('app_can_access_reviews')) {
    function app_can_access_reviews(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $allowedRoles = [
            User::ROLE_MARKETING,
            User::ROLE_MANAGEMENT,
        ];

        return app_user_has_any_role($user, $allowedRoles);
    }
}

if (! function_exists('app_can_access_curriculums')) {
    function app_can_access_curriculums(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return app_user_has_any_role($user, [User::ROLE_HUMAN_RESOURCES]);
    }
}

if (! function_exists('app_can_access_tickets')) {
    function app_can_access_tickets(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return app_user_has_any_role($user, [User::ROLE_INFORMATION_TECHNOLOGY]);
    }
}

if (! function_exists('app_can_see_tickets_navigation')) {
    function app_can_see_tickets_navigation(?User $user = null): bool
    {
        return app_can_access_tickets($user);
    }
}

if (! function_exists('app_admin_permission_definitions')) {
    function app_admin_permission_definitions(): array
    {
        return config('admin_permissions.permissions', []);
    }
}

if (! function_exists('app_admin_permission_keys')) {
    function app_admin_permission_keys(): array
    {
        return array_keys(app_admin_permission_definitions());
    }
}

if (! function_exists('app_default_admin_permissions_for')) {
    function app_default_admin_permissions_for(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        return collect(app_admin_permission_definitions())
            ->filter(function (array $definition) use ($user): bool {
                return in_array($user->role, $definition['default_roles'] ?? [], true);
            })
            ->keys()
            ->values()
            ->all();
    }
}

if (! function_exists('app_user_has_admin_permission')) {
    function app_user_has_admin_permission(?User $user, string $permissionKey): bool
    {
        if (! $user) {
            return false;
        }

        if (! in_array($permissionKey, app_admin_permission_keys(), true)) {
            return false;
        }

        if (in_array($permissionKey, app_default_admin_permissions_for($user), true)) {
            return true;
        }

        if ($user->adminPermissionGrants()->where('permission_key', $permissionKey)->exists()) {
            return true;
        }

        if (filled($user->extra_role) && AdminPermissionGrant::query()
            ->where('permission_key', $permissionKey)
            ->where('group_role', $user->extra_role)
            ->exists()) {
            return true;
        }

        return false;
    }
}

if (! function_exists('app_role_has_admin_permission')) {
    function app_role_has_admin_permission(?string $role, string $permissionKey): bool
    {
        if (! is_string($role) || $role === '') {
            return false;
        }

        if (! in_array($permissionKey, app_admin_permission_keys(), true)) {
            return false;
        }

        $definition = app_admin_permission_definitions()[$permissionKey] ?? [];
        $defaultRoles = $definition['default_roles'] ?? [];

        if (in_array($role, $defaultRoles, true)) {
            return true;
        }

        return AdminPermissionGrant::query()
            ->where('permission_key', $permissionKey)
            ->where('group_role', $role)
            ->exists();
    }
}

if (! function_exists('app_user_can_access_admin_panel')) {
    function app_user_can_access_admin_panel(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return collect(app_admin_permission_keys())
            ->contains(fn (string $permissionKey): bool => app_user_has_admin_permission($user, $permissionKey));
    }
}

if (! function_exists('app_user_can_view_admin_logs')) {
    function app_user_can_view_admin_logs(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && $user->role === User::ROLE_ADMIN;
    }
}

if (! function_exists('app_user_can_manage_admin_tool')) {
    function app_user_can_manage_admin_tool(?User $user, string $permissionKey): bool
    {
        return app_user_has_admin_permission($user, $permissionKey);
    }
}

if (! function_exists('app_user_can_manage_admin_permissions')) {
    function app_user_can_manage_admin_permissions(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && $user->role === User::ROLE_ADMIN;
    }
}

if (! function_exists('app_admin_permission_key_for_route')) {
    function app_admin_permission_key_for_route(?string $routeName): ?string
    {
        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        return match (true) {
            Str::startsWith($routeName, ['users.']) => 'users.manage',
            Str::startsWith($routeName, ['dealerships.']) => 'dealerships.manage',
            Str::startsWith($routeName, ['tickets.']) => 'tickets-it.manage',
            default => null,
        };
    }
}

if (! function_exists('app_visible_role')) {
    function app_visible_role(?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        $viewerRole = session('role_viewer.active_role');
        $allowedRoles = array_keys(app_role_viewer_options($user));

        if (app_role_viewer_enabled($user) && is_string($viewerRole) && in_array($viewerRole, $allowedRoles, true)) {
            return $viewerRole;
        }

        return $user->role;
    }
}

if (! function_exists('app_real_role')) {
    function app_real_role(?User $user = null): ?string
    {
        return ($user ?? auth()->user())?->role;
    }
}

if (! function_exists('app_role_viewer_active')) {
    function app_role_viewer_active(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! app_role_viewer_enabled($user)) {
            return false;
        }

        $viewerRole = session('role_viewer.active_role');
        $allowedRoles = array_keys(app_role_viewer_options($user));

        return is_string($viewerRole) && $viewerRole !== $user->role && in_array($viewerRole, $allowedRoles, true);
    }
}

if (! function_exists('app_visible_role_label')) {
    function app_visible_role_label(?User $user = null): string
    {
        $role = app_visible_role($user);

        return User::roleLabels()[$role] ?? 'Admin';
    }
}

if (! function_exists('app_role_viewer_enabled')) {
    function app_role_viewer_enabled(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_INFORMATION_TECHNOLOGY,
        ], true);
    }
}

if (! function_exists('app_role_viewer_options')) {
    function app_role_viewer_options(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->role === User::ROLE_ADMIN) {
            return array_filter(
                User::roleLabels(),
                fn (string $label, string $role): bool => $role !== User::ROLE_USER,
                ARRAY_FILTER_USE_BOTH
            );
        }

        if ($user->role === User::ROLE_MANAGER) {
            return array_merge(
                [User::ROLE_MANAGER => User::roleLabels()[User::ROLE_MANAGER] ?? 'Gestor'],
                User::extraRoleLabels()
            );
        }

        if ($user->role === User::ROLE_INFORMATION_TECHNOLOGY) {
            return User::extraRoleLabels();
        }

        return [];
    }
}

if (! function_exists('app_salesforce_url_for')) {
    function app_salesforce_url_for(?User $user = null): string
    {
        $visibleRole = app_visible_role($user);

        if (in_array($visibleRole, [User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER], true)) {
            return 'https://hrmotor.lightning.force.com/lightning/n/Veh_culos';
        }

        return config('portal.links.tools.salesforce_comunidad');
    }
}

if (! function_exists('app_it_support_url_for')) {
    function app_it_support_url_for(?User $user = null): string
    {
        return route('it-tickets.index');
    }
}
