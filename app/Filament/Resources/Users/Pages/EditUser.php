<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserInvitationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resendActivationEmail')
                ->label('Reenviar correo de activación')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->visible(fn (): bool => ! $this->getRecord()->isDisabled() && (
                    $this->getRecord()->isInvitationExpired()
                    || ! $this->getRecord()->is_active
                    || $this->getRecord()->must_change_password
                ))
                ->requiresConfirmation()
                ->modalHeading('Reenviar correo de activación')
                ->modalDescription(fn (): string => $this->buildResendInvitationWarning())
                ->action(function (): void {
                    $status = app(UserInvitationService::class)->resend($this->getRecord());

                    if ($status === UserInvitationService::DELIVERY_FAILED) {
                        Notification::make()
                            ->danger()
                            ->title('El servidor SMTP ha rechazado el correo de activación.')
                            ->body('Comprueba que ese buzón exista y que el servidor de correo de HR Motor lo acepte.')
                            ->send();

                        return;
                    }

                    if ($status !== Password::RESET_LINK_SENT) {
                        Notification::make()
                            ->danger()
                            ->title(match ($status) {
                                Password::RESET_THROTTLED => 'Espera un momento antes de volver a enviar el correo de activación.',
                                Password::INVALID_USER => 'No se ha encontrado un usuario válido para enviar el correo de activación.',
                                default => 'No se ha podido enviar el correo de activación.',
                            })
                            ->body(match ($status) {
                                Password::RESET_THROTTLED => 'Solo han pasado unos minutos desde el último envío. Espera un poco antes de intentarlo de nuevo.',
                                default => null,
                            })
                            ->send();

                        return;
                    }

                    $this->record->refresh();

                    Notification::make()
                        ->success()
                        ->title('Correo de activación reenviado correctamente.')
                        ->body('El estado de la cuenta ha pasado a Pendiente.')
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

        return $data;
    }

    protected function buildResendInvitationWarning(): string
    {
        $record = $this->getRecord();

        if ($record->isInvitationExpired()) {
            return 'Vas a reenviar el correo de activación para que la persona pueda definir su contraseña.';
        }

        $sentAt = $record->invitation_sent_at ?? $record->created_at;
        $elapsedMinutes = $sentAt ? max(1, now()->diffInMinutes($sentAt)) : null;
        $elapsedText = $elapsedMinutes === null
            ? 'muy poco tiempo'
            : ($elapsedMinutes === 1 ? '1 minuto' : "{$elapsedMinutes} minutos");

        return "Solo han pasado {$elapsedText} desde el último reenvío. ¿Seguro que quieres reenviarlo de nuevo?";
    }
}
