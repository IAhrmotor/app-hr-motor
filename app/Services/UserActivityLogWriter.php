<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;

class UserActivityLogWriter
{
    public function record(
        User $actor,
        User $targetUser,
        string $action,
        array $changes = [],
        string $result = 'success',
        ?string $reason = null,
    ): void {
        UserActivityLog::query()->create([
            'action' => $action,
            'result' => $result,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_user_id' => $targetUser->id,
            'target_name' => $targetUser->name,
            'target_email' => $targetUser->email,
            'target_role' => $targetUser->role,
            'target_dealership' => $targetUser->dealership,
            'changes' => $changes === [] ? null : $changes,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
