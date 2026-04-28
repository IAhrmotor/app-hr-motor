<?php

use App\Models\User;

if (! function_exists('app_role_viewer_options')) {
    function app_role_viewer_options(): array
    {
        return array_filter(
            User::roleLabels(),
            fn (string $label, string $role): bool => $role !== User::ROLE_USER,
            ARRAY_FILTER_USE_BOTH
        );
    }
}

if (! function_exists('app_effective_roles')) {
    function app_effective_roles(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->role === User::ROLE_ADMIN) {
            $viewerRole = session('role_viewer.active_role');

            if (is_string($viewerRole) && $viewerRole !== $user->role && array_key_exists($viewerRole, app_role_viewer_options())) {
                return [$viewerRole];
            }

            return [User::ROLE_ADMIN];
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
            User::ROLE_MANAGEMENT,
        ];

        if ($user->role === User::ROLE_ADMIN) {
            return app_role_viewer_active($user) && in_array(app_visible_role($user), $allowedRoles, true);
        }

        return app_user_has_any_role($user, $allowedRoles);
    }
}

if (! function_exists('app_can_access_forum')) {
    function app_can_access_forum(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $allowedRoles = [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_COMMERCIAL,
            User::ROLE_STORE_MANAGER,
            User::ROLE_AREA_MANAGER,
            User::ROLE_MANAGEMENT,
        ];

        if ($user->role === User::ROLE_ADMIN) {
            return ! app_role_viewer_active($user) || in_array(app_visible_role($user), $allowedRoles, true);
        }

        if ($user->role === User::ROLE_MANAGER) {
            return true;
        }

        return app_user_has_any_role($user, $allowedRoles);
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

if (! function_exists('app_visible_role')) {
    function app_visible_role(?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        $viewerRole = session('role_viewer.active_role');

        if ($user->role === User::ROLE_ADMIN && in_array($viewerRole, array_keys(app_role_viewer_options()), true)) {
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

        if (! $user || $user->role !== User::ROLE_ADMIN) {
            return false;
        }

        $viewerRole = session('role_viewer.active_role');

        return is_string($viewerRole) && $viewerRole !== $user->role && array_key_exists($viewerRole, app_role_viewer_options());
    }
}

if (! function_exists('app_visible_role_label')) {
    function app_visible_role_label(?User $user = null): string
    {
        $role = app_visible_role($user);

        return User::roleLabels()[$role] ?? 'Admin';
    }
}
