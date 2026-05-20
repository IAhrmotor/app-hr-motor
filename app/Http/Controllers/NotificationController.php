<?php

namespace App\Http\Controllers;

use App\Models\ForumThread;
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

        $userNotification->markAsRead();

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

        $notifications = $authUser
            ? $authUser->unreadNotifications()
                ->latest()
                ->take(8)
                ->get()
                ->map(static function ($notification): array {
                    $isPriority = (bool) data_get($notification->data, 'priority', false);

                    return [
                        'id' => $notification->id,
                        'title' => data_get($notification->data, 'title', data_get($notification->data, 'message', 'Notificación')),
                        'description' => data_get($notification->data, 'description', data_get($notification->data, 'thread_title', '')),
                        'link_url' => data_get($notification->data, 'link_url', data_get($notification->data, 'thread_url')),
                        'link_label' => data_get($notification->data, 'link_label', 'Abrir'),
                        'priority' => $isPriority,
                        'created_at_label' => $notification->created_at?->diffForHumans(),
                    ];
                })
            : collect();

        return response()->json([
            'count' => $authUser ? $authUser->unreadNotifications()->count() : 0,
            'notifications' => $notifications,
        ]);
    }
}
