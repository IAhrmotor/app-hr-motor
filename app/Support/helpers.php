<?php

use App\Models\User;

if (! function_exists('app_visible_role')) {
    function app_visible_role(?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        $viewerRole = session('role_viewer.active_role');

        if ($user->role === User::ROLE_ADMIN && in_array($viewerRole, array_keys(User::roleLabels()), true)) {
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

        return is_string($viewerRole) && $viewerRole !== $user->role && array_key_exists($viewerRole, User::roleLabels());
    }
}

if (! function_exists('app_visible_role_label')) {
    function app_visible_role_label(?User $user = null): string
    {
        $role = app_visible_role($user);

        return User::roleLabels()[$role] ?? 'Admin';
    }
}
