<?php

namespace App\Policies;

use App\Models\CompanyChatGroup;
use App\Models\User;

class CompanyChatGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return app_user_has_admin_permission($user, 'chat-groups.manage');
    }

    public function view(User $user, CompanyChatGroup $group): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, CompanyChatGroup $group): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, CompanyChatGroup $group): bool
    {
        return $this->viewAny($user);
    }
}
