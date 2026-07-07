<?php

namespace App\Services;

use App\Events\UserNotificationBadgeUpdated;
use App\Models\User;

class NotificationBadgeBroadcaster
{
    public function broadcast(User $user): void
    {
        event(new UserNotificationBadgeUpdated(
            (int) $user->id,
            $this->unreadCountFor($user),
        ));
    }

    public function unreadCountFor(User $user): int
    {
        return (int) $user->unreadNotifications()->count();
    }
}
