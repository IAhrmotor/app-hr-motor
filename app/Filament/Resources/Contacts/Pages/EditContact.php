<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\Contact;
use App\Models\ContentActivityLog;
use App\Models\User;
use App\Services\ContentActivityLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    protected array $pendingActivityLogChanges = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar')
                ->using(function (Contact $record): bool {
                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        return false;
                    }

                    app(ContentActivityLogger::class)->record(
                        actor: $actor,
                        contentType: ContentActivityLog::CONTENT_TYPE_CONTACT,
                        action: ContentActivityLog::ACTION_DELETED,
                        targetName: $record->name,
                        targetReference: $record->enreach_extension ?: $record->phone,
                        changes: [
                            'name' => ['from' => $record->name, 'to' => null],
                            'email' => ['from' => $record->email, 'to' => null],
                            'phone' => ['from' => $record->phone, 'to' => null],
                            'enreach_extension' => ['from' => $record->enreach_extension, 'to' => null],
                        ],
                    );

                    return (bool) $record->delete();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingActivityLogChanges = $this->buildChangeSet($this->getRecord(), $data);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingActivityLogChanges === []) {
            return;
        }

        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        /** @var Contact $contact */
        $contact = $this->getRecord();

        app(ContentActivityLogger::class)->record(
            actor: $actor,
            contentType: ContentActivityLog::CONTENT_TYPE_CONTACT,
            action: ContentActivityLog::ACTION_UPDATED,
            targetName: $contact->name,
            targetReference: $contact->enreach_extension ?: $contact->phone,
            changes: $this->pendingActivityLogChanges,
        );

        $this->pendingActivityLogChanges = [];
    }

    protected function buildChangeSet(Contact $contact, array $newValues): array
    {
        $labels = [
            'name' => 'Nombre',
            'email' => 'Correo',
            'phone' => 'Teléfono',
            'enreach_extension' => 'Extensión Enreach',
        ];

        return collect($newValues)
            ->filter(fn ($value, $field) => $this->compareContactFieldValue($contact, $field, $value))
            ->mapWithKeys(fn ($value, $field) => [
                $labels[$field] ?? $field => [
                    'from' => $contact->{$field},
                    'to' => $value,
                ],
            ])
            ->all();
    }

    protected function compareContactFieldValue(Contact $contact, string $field, mixed $newValue): bool
    {
        if (in_array($field, ['phone', 'enreach_extension'], true)) {
            return $this->normalizeContactValue($contact->{$field}) !== $this->normalizeContactValue($newValue);
        }

        return $contact->{$field} !== $newValue;
    }

    protected function normalizeContactValue(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}
