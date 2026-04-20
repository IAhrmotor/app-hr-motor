<?php

namespace App\Http\Controllers;

use App\Models\NotificationActivityLog;
use App\Models\User;
use App\Notifications\AdminPriorityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminNotificationController extends Controller
{
    public function create(Request $request)
    {
        $authUser = $request->user();
        $availableRoles = User::notificationTargetRolesFor($authUser);

        return view('admin.notifications.create', compact('availableRoles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = $request->user();
        $availableRoles = User::notificationTargetRolesFor($authUser);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($availableRoles)],
        ]);

        $recipientQuery = User::query()
            ->where('is_active', true)
            ->whereIn('role', $validated['roles']);

        $recipients = $recipientQuery->get();

        if ($recipients->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'No hay usuarios activos con los roles seleccionados.');
        }

        Notification::send(
            $recipients,
            new AdminPriorityNotification(
                title: $validated['title'],
                description: $validated['description'],
                linkUrl: $validated['link_url'] ?? null,
                actor: $authUser,
            )
        );

        if (Schema::hasTable('notification_activity_logs')) {
            NotificationActivityLog::query()->create([
                'actor_user_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'actor_email' => $authUser->email,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'link_url' => $validated['link_url'] ?? null,
                'target_roles' => array_values($validated['roles']),
                'recipient_count' => $recipients->count(),
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.notifications.create')
            ->with('success', 'Notificación enviada correctamente a ' . $recipients->count() . ' usuario(s).');
    }
}
