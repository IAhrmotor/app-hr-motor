<?php

namespace App\Policies;

use App\Models\User;

class CommercialCommissionPolicy
{
    public function view(User $user): bool
    {
        return $this->isEligibleCommercial($user);
    }

    private function isEligibleCommercial(User $user): bool
    {
        return $user->role === User::ROLE_USER
            && in_array($user->extra_role, [
                User::ROLE_COMMERCIAL,
                User::ROLE_STORE_MANAGER,
                User::ROLE_AREA_MANAGER,
                User::ROLE_HR_NEWCARS,
            ], true)
            && filled($user->salesforce_user_id);
    }
}
