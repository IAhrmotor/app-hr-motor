<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Dealership;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Notifications\UserWelcomeNotification;
use App\Services\UserActivityLogWriter;
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

        $dealership = filled($data['dealership_id'] ?? null)
            ? Dealership::query()->find($data['dealership_id'])
            : null;

        return [
            ...$data,
            'dealership' => $dealership?->name,
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
        $actor = auth()->user();

        if ($actor instanceof User) {
            app(UserActivityLogWriter::class)->record(
                actor: $actor,
                targetUser: $user,
                action: UserActivityLog::ACTION_CREATED,
            );
        }

        $user->notify(new UserWelcomeNotification());

        Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);
    }
}
