<?php

namespace App\Http\Controllers;

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

        $targetUrl = data_get($userNotification->data, 'thread_url', route('forum.index'));

        return redirect()->to($targetUrl);
    }
}
