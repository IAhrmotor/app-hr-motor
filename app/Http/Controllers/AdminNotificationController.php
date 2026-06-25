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
    private const TARGET_ALL_USERS = '__all_users__';

    public function create(Request $request)
    {
        $authUser = $request->user();
        $availableTargets = $this->availableTargetsFor($authUser);

        return view('admin.notifications.create', compact('availableTargets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = $request->user();
        $availableTargets = $this->availableTargetsFor($authUser);
        $availableTargetValues = array_column($availableTargets, 'value');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($availableTargetValues)],
        ]);

        $selectedTargets = array_values(array_intersect($validated['roles'], $availableTargetValues));
        $sendToAllUsers = in_array(self::TARGET_ALL_USERS, $selectedTargets, true);

        if ($sendToAllUsers) {
            $selectedTargets = [self::TARGET_ALL_USERS];
        }

        $recipientQuery = User::query()->where('is_active', true);

        if (! $sendToAllUsers) {
            $recipientQuery->where(function ($query) use ($selectedTargets): void {
                $query->whereIn('role', $selectedTargets)
                    ->orWhereIn('extra_role', $selectedTargets);
            });
        }

        $recipients = $recipientQuery->get();

        if ($recipients->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', $sendToAllUsers
                    ? 'No hay usuarios activos para recibir la notificación.'
                    : 'No hay usuarios activos con los roles seleccionados.');
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
                'target_roles' => $selectedTargets,
                'recipient_count' => $recipients->count(),
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.notifications.create')
            ->with('success', 'Notificación enviada correctamente a ' . $recipients->count() . ' usuario(s).');
    }

    private function availableTargetsFor(User $authUser): array
    {
        $roleLabels = User::roleLabels();
        $availableRoles = User::notificationTargetRolesFor($authUser);

        $targets = [[
            'value' => self::TARGET_ALL_USERS,
            'label' => 'Todos los usuarios',
            'description' => 'Se enviará a todos los usuarios activos del portal.',
            'highlighted' => true,
        ]];

        foreach ($availableRoles as $role) {
            $targets[] = [
                'value' => $role,
                'label' => $roleLabels[$role] ?? $role,
                'description' => match ($role) {
                    User::ROLE_ADMIN => 'Usuarios con permisos totales del portal.',
                    User::ROLE_MANAGER => 'Gestores con acceso al área de administración.',
                    User::ROLE_STORE_MANAGER => 'Jefes de tienda.',
                    default => 'Usuarios con ese rol adicional.',
                },
                'highlighted' => false,
            ];
        }

        return $targets;
    }
}
