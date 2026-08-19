<?php

namespace App\Filament\Resources\Dealerships\Pages;

use App\Filament\Resources\Dealerships\DealershipResource;
use App\Models\Dealership;
use App\Models\DealershipActivityLog;
use App\Models\User;
use App\Services\DealershipActivityLogWriter;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDealership extends EditRecord
{
    protected static string $resource = DealershipResource::class;

    protected array $pendingActivityLogChanges = [];

    protected ?string $previousImagePath = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar')
                ->disabled(fn (): bool => $this->getRecord()->users()->exists())
                ->tooltip(fn (): ?string => $this->getRecord()->users()->exists()
                    ? 'No puedes eliminar una delegación con usuarios asignados.'
                    : 'Eliminar delegación')
                ->using(function (Dealership $record): bool {
                    if ($record->users()->exists()) {
                        return false;
                    }

                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        return false;
                    }

                    app(DealershipActivityLogWriter::class)->record(
                        actor: $actor,
                        dealership: $record,
                        action: DealershipActivityLog::ACTION_DELETED,
                    );

                    Dealership::deleteStoredImagePath($record->image_path);

                    return (bool) $record->delete();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousImagePath = $this->getRecord()->image_path;
        $this->pendingActivityLogChanges = $this->buildChangeSet($this->getRecord(), $data);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->previousImagePath !== null && $this->previousImagePath !== $this->getRecord()->image_path) {
            Dealership::deleteStoredImagePath($this->previousImagePath);
        }

        if ($this->pendingActivityLogChanges === []) {
            $this->previousImagePath = null;

            return;
        }

        $actor = auth()->user();

        if (! $actor instanceof User) {
            $this->previousImagePath = null;

            return;
        }

        app(DealershipActivityLogWriter::class)->record(
            actor: $actor,
            dealership: $this->getRecord(),
            action: DealershipActivityLog::ACTION_UPDATED,
            changes: $this->pendingActivityLogChanges,
        );

        $this->pendingActivityLogChanges = [];
        $this->previousImagePath = null;
    }

    protected function buildChangeSet(Dealership $dealership, array $newValues): array
    {
        $labels = [
            'name' => 'Nombre',
            'image_path' => 'Foto',
            'salesforce_id' => 'ID Salesforce',
            'phone' => 'Teléfono',
            'google_maps_url' => 'URL Google Maps',
            'reviews_url' => 'URL Reseñas',
        ];

        return collect($newValues)
            ->filter(fn ($value, $field) => $dealership->{$field} !== $value)
            ->mapWithKeys(fn ($value, $field) => [
                $labels[$field] ?? $field => [
                    'from' => $field === 'image_path'
                        ? (filled($dealership->{$field}) ? 'Anterior' : null)
                        : $dealership->{$field},
                    'to' => $field === 'image_path'
                        ? (filled($value) ? 'Actualizada' : null)
                        : $value,
                ],
            ])
            ->all();
    }
}
