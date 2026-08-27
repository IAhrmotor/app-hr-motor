<?php

namespace App\Services;

use App\Models\BulletinActivityLog;
use App\Models\BulletinPost;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class BulletinActivityLogWriter
{
    public function record(
        User $actor,
        string $action,
        BulletinPost $bulletin,
        array $changes = [],
    ): void {
        if (! Schema::hasTable('bulletin_activity_logs')) {
            return;
        }

        BulletinActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_bulletin_post_id' => $bulletin->id,
            'target_name' => $bulletin->title,
            'target_reference' => $bulletin->published_at?->format('Y-m-d H:i:s'),
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
