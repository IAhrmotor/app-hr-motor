<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true);
    }

    public function view(User $user, User $model): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return $user->role === User::ROLE_MANAGER
            && $user->id !== $model->id
            && $model->role === User::ROLE_USER;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return $user->id !== $model->id;
        }

        return $user->role === User::ROLE_MANAGER
            && $user->id !== $model->id
            && $model->role === User::ROLE_USER;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }
}
