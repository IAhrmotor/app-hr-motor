<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Throwable;

class UserPasswordResetService
{
    public const DELIVERY_FAILED = 'password_reset_delivery_failed';

    /**
     * @return string One of the Password broker status constants or DELIVERY_FAILED.
     */
    public function send(User $user): string
    {
        try {
            return Password::broker()->sendResetLink([
                'email' => $user->email,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return self::DELIVERY_FAILED;
        }
    }
}
