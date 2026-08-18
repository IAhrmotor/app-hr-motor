<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Throwable;

class UserInvitationService
{
    public const DELIVERY_FAILED = 'invitation_delivery_failed';

    /**
     * @return string One of the Password broker status constants or DELIVERY_FAILED.
     */
    public function resend(User $user): string
    {
        try {
            $status = Password::broker()->sendResetLink([
                'email' => $user->email,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return self::DELIVERY_FAILED;
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return $status;
        }

        $user->forceFill([
            'is_active' => false,
            'must_change_password' => true,
            'activated_at' => null,
            'invitation_sent_at' => now(),
        ])->save();

        return $status;
    }
}
