<?php

namespace App\Services;

use App\Models\User;

class EnreachExtensionConflictResolver
{
    public function normalize(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    public function resolveConflictMessage(mixed $value, ?int $ignoreUserId = null, string $action = 'crear'): ?string
    {
        $extension = $this->normalize($value);

        if ($extension === null) {
            return null;
        }

        $query = User::query()
            ->where('enreach_extension', $extension);

        if ($ignoreUserId !== null) {
            $query->whereKeyNot($ignoreUserId);
        }

        $conflictingUser = $query->first();

        if (! $conflictingUser) {
            return null;
        }

        return $this->buildConflictMessage($action, $conflictingUser, $extension);
    }

    public function buildConflictMessage(string $action, User $conflictingUser, string $extension): string
    {
        $actionLabel = $action === 'editar' ? 'editar' : 'crear';

        return sprintf(
            'No se puede %s el usuario porque la extensión de Enreach %s ya está asignada al usuario "%s" con estado "%s".',
            $actionLabel,
            $extension,
            $conflictingUser->name,
            $this->resolveUserStatusLabel($conflictingUser),
        );
    }

    public function resolveUserStatusLabel(User $user): string
    {
        if ($user->isDisabled()) {
            return 'Desactivado';
        }

        if ($user->is_active) {
            return 'Activo';
        }

        if ($user->isInvitationExpired()) {
            return 'Caducado';
        }

        return 'Pendiente';
    }
}
