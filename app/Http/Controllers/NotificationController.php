<?php

namespace App\Http\Controllers;

use App\Models\ForumThread;
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
}
