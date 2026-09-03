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
            && $user->extra_role === User::ROLE_COMMERCIAL
            && filled($user->salesforce_user_id);
    }
}
