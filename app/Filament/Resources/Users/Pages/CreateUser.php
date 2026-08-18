<?php

namespace App\Filament\Resources\Users\Pages;

use App\Models\User;
use App\Notifications\UserWelcomeNotification;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['extra_role'] ?? null) !== User::ROLE_INFORMATION_TECHNOLOGY) {
            foreach ([
                'it_monday_start',
                'it_monday_end',
                'it_tuesday_start',
                'it_tuesday_end',
                'it_wednesday_start',
                'it_wednesday_end',
                'it_thursday_start',
                'it_thursday_end',
                'it_friday_start',
                'it_friday_end',
            ] as $field) {
                $data[$field] = null;
            }
        }

        return [
            ...$data,
            'password' => Hash::make(Str::password(32)),
            'is_active' => false,
            'must_change_password' => true,
            'activated_at' => null,
            'invitation_sent_at' => now(),
        ];
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;

        $user->notify(new UserWelcomeNotification());

        Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);
    }
}
