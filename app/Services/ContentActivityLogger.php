<?php

namespace App\Services;

use App\Models\ContentActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ContentActivityLogger
{
    public function record(
        User $actor,
        string $contentType,
        string $action,
        string $targetName,
        ?string $targetReference = null,
        array $changes = [],
    ): void {
        if (! Schema::hasTable('content_activity_logs')) {
            return;
        }

        ContentActivityLog::query()->create([
            'content_type' => $contentType,
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_name' => $targetName,
            'target_reference' => $targetReference,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
