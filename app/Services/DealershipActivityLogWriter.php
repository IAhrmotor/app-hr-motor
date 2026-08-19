<?php

namespace App\Services;

use App\Models\Dealership;
use App\Models\DealershipActivityLog;
use App\Models\User;

class DealershipActivityLogWriter
{
    public function record(
        User $actor,
        Dealership $dealership,
        string $action,
        array $changes = [],
    ): void {
        DealershipActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_dealership_id' => $dealership->id,
            'target_name' => $dealership->name,
            'target_salesforce_id' => $dealership->salesforce_id,
            'target_phone' => $dealership->phone,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
        ]);
    }
}
