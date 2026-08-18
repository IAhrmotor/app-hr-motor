<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\CompanyChatDefaultGroupSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class UserDeactivationService
{
    public function canDeactivate(?User $actor, User $target): bool
    {
        return $this->deactivationError($actor, $target) === null;
    }

    public function deactivate(User $actor, User $target, ?string $reason = null): void
    {
        if ($error = $this->deactivationError($actor, $target)) {
            throw new RuntimeException($error);
        }

        $disabledReason = trim((string) $reason);

        if ($disabledReason === '') {
            $disabledReason = 'Baja de empleado';
        }

        DB::transaction(function () use ($actor, $target, $disabledReason): void {
            $target->forceFill([
                'is_active' => false,
                'disabled_at' => now(),
                'disabled_by' => $actor->id,
                'disabled_reason' => $disabledReason,
                'remember_token' => Str::random(60),
            ])->save();

            app(CompanyChatDefaultGroupSyncService::class)->syncUser($target, false);
            $this->invalidateUserSessions($target);

            UserActivityLog::query()->create([
                'action' => UserActivityLog::ACTION_DISABLED,
                'result' => 'success',
                'actor_user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'target_user_id' => $target->id,
                'target_name' => $target->name,
                'target_email' => $target->email,
                'target_role' => $target->role,
                'target_dealership' => $target->dealership,
                'changes' => null,
                'reason' => $disabledReason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        });
    }

    private function deactivationError(?User $actor, User $target): ?string
    {
        if (! $actor) {
            return 'No tienes permisos para desactivar este usuario.';
        }

        if ($actor->id === $target->id) {
            return 'No puedes desactivar tu propio usuario.';
        }

        if (! in_array($actor->role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true)) {
            return 'No tienes permisos para desactivar este usuario.';
        }

        if ($target->isDisabled() || ! $target->is_active) {
            return 'Solo puedes desactivar usuarios activos.';
        }

        if ($actor->role !== User::ROLE_ADMIN && $target->role !== User::ROLE_USER) {
            return 'No tienes permisos para desactivar este usuario.';
        }

        if ($target->role === User::ROLE_ADMIN) {
            $otherActiveAdmins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->whereNull('disabled_at')
                ->whereKeyNot($target->id)
                ->count();

            if ($otherActiveAdmins === 0) {
                return 'No puedes desactivar al ultimo administrador activo.';
            }
        }

        return null;
    }

    private function invalidateUserSessions(User $user): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
    }
}
