<?php

namespace App\Http\Controllers;

use App\Models\ForumThread;
use App\Notifications\CompanyChatMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function show(Request $request, string $notification): RedirectResponse
    {
        $userNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($userNotification->type === CompanyChatMessageNotification::class) {
            $groupKey = data_get($userNotification->data, 'chat_group_key');
            $conversationId = data_get($userNotification->data, 'conversation_id');
            $senderId = data_get($userNotification->data, 'sender_id');

            $request->user()
                ->unreadNotifications()
                ->where('type', CompanyChatMessageNotification::class)
                ->when(
                    filled($groupKey),
                    fn ($query) => $query->where('data->chat_group_key', $groupKey),
                    fn ($query) => $query
                        ->where('data->conversation_id', $conversationId)
                        ->where('data->sender_id', $senderId)
                )
                ->update(['read_at' => now()]);
        } else {
            $userNotification->markAsRead();
        }

        $threadId = data_get($userNotification->data, 'thread_id');

        if ($threadId && ! ForumThread::query()->whereKey($threadId)->exists()) {
            $userNotification->delete();

            return redirect()
                ->route('forum.index')
                ->with('error', 'Ese hilo del foro ya no existe.');
        }

        $targetUrl = data_get(
            $userNotification->data,
            'link_url',
            data_get($userNotification->data, 'thread_url', route('home'))
        );

        return redirect()->to($targetUrl);
    }

    public function summary(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $notifications = $authUser ? $this->groupUnreadNotifications($authUser) : collect();
        $rawChatNotifications = $authUser ? $this->rawUnreadChatNotifications($authUser) : collect();

        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications,
            'raw_notifications' => $rawChatNotifications,
        ]);
    }

    private function groupUnreadNotifications($authUser)
    {
        return $authUser
            ->unreadNotifications()
            ->latest()
            ->get()
            ->groupBy(function ($notification): string {
                if ($notification->type === CompanyChatMessageNotification::class) {
                    $groupKey = data_get($notification->data, 'chat_group_key');
                    $conversationId = data_get($notification->data, 'conversation_id');
                    $senderId = data_get($notification->data, 'sender_id');

                    return 'chat:' . ($groupKey ?: ($conversationId . ':' . $senderId));
                }

                return 'notification:' . $notification->id;
            })
            ->map(function ($group): array {
                $notification = $group->first();
                $isPriority = (bool) data_get($notification->data, 'priority', false);
                $isChatMessage = $notification->type === CompanyChatMessageNotification::class;
                $messageCount = $group->count();
                $sortTimestamp = $notification->created_at?->timestamp ?? 0;

                $description = data_get($notification->data, 'description', data_get($notification->data, 'thread_title', ''));

                if ($isChatMessage && $messageCount > 1) {
                    $description = trim((string) $description);
                    $description = $description !== ''
                        ? $description . ' · ' . $messageCount . ' mensajes'
                        : $messageCount . ' mensajes';
                }

                return [
                    'id' => $notification->id,
                    'type' => data_get($notification->data, 'type', $notification->type),
                    'title' => data_get($notification->data, 'title', data_get($notification->data, 'message', 'Notificación')),
                    'description' => $description,
                    'link_url' => data_get($notification->data, 'link_url', data_get($notification->data, 'thread_url')),
                    'link_label' => data_get($notification->data, 'link_label', 'Abrir'),
                    'priority' => $isPriority,
                    'created_at_label' => $notification->created_at?->diffForHumans(),
                    'message_count' => $messageCount,
                    'sort_timestamp' => $sortTimestamp,
                    'actor_name' => data_get($notification->data, 'actor_name'),
                    'actor_avatar_url' => data_get($notification->data, 'actor_avatar_url'),
                    'chat_group_key' => data_get($notification->data, 'chat_group_key'),
                ];
            })
            ->sortByDesc(fn (array $notification): int => (int) ($notification['sort_timestamp'] ?? 0))
            ->values();
    }

    private function rawUnreadChatNotifications($authUser)
    {
        return $authUser
            ->unreadNotifications()
            ->where('type', CompanyChatMessageNotification::class)
            ->latest()
            ->limit(25)
            ->get()
            ->map(function ($notification): array {
                return [
                    'id' => $notification->id,
                    'type' => data_get($notification->data, 'type', $notification->type),
                    'title' => data_get($notification->data, 'title', 'Nuevo mensaje'),
                    'description' => data_get($notification->data, 'description', ''),
                    'link_url' => data_get($notification->data, 'link_url'),
                    'actor_name' => data_get($notification->data, 'actor_name'),
                    'actor_avatar_url' => data_get($notification->data, 'actor_avatar_url'),
                    'chat_group_key' => data_get($notification->data, 'chat_group_key'),
                    'created_at_label' => $notification->created_at?->diffForHumans(),
                ];
            })
            ->values();
    }
}
