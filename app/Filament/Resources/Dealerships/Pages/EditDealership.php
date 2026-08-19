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

        app(DealershipActivityLogWriter::class)->record(
            actor: $actor,
            dealership: $this->getRecord(),
            action: DealershipActivityLog::ACTION_UPDATED,
            changes: $this->pendingActivityLogChanges,
        );

        $this->pendingActivityLogChanges = [];
    }

    protected function buildChangeSet(Dealership $dealership, array $newValues): array
    {
        $labels = [
            'name' => 'Nombre',
            'salesforce_id' => 'ID Salesforce',
            'phone' => 'Teléfono',
            'google_maps_url' => 'URL Google Maps',
            'reviews_url' => 'URL Reseñas',
        ];

        return collect($newValues)
            ->filter(fn ($value, $field) => $dealership->{$field} !== $value)
            ->mapWithKeys(fn ($value, $field) => [
                $labels[$field] ?? $field => [
                    'from' => $dealership->{$field},
                    'to' => $value,
                ],
            ])
            ->all();
    }
}
