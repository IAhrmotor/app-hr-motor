<?php

use App\Models\CompanyChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.conversations.{conversationId}', function (User $user, int $conversationId): bool {
    return CompanyChatConversation::query()
        ->whereKey($conversationId)
        ->first()
        ?->involves($user) ?? false;
});

Broadcast::channel('chat.users.{userId}', function (User $user, int $userId): bool {
    return (int) $user->id === (int) $userId;
});
