<?php

namespace App\Filament\Resources\Zones\Pages;

use App\Filament\Resources\Zones\ZoneResource;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneActivityLog;
use App\Services\ZoneManagementService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZone extends EditRecord
{
    protected static string $resource = ZoneResource::class;

    protected array $pendingDealershipIds = [];

    protected array $pendingActivityLogChanges = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('dealerships');

        $data['dealership_ids'] = $this->record->dealerships->pluck('id')->all();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar')
                ->using(function (Zone $record): bool {
                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        return false;
                    }

                    $record->loadMissing('dealerships');
                    $service = app(ZoneManagementService::class);
                    $dealershipNames = $service->dealershipNames($record->dealerships->pluck('id')->all());

                    $service->syncDealershipAssignments($record, []);
                    $service->recordActivityLog(
                        actor: $actor,
                        zone: $record,
                        action: ZoneActivityLog::ACTION_DELETED,
                        changes: [
                            'Nombre' => ['from' => $record->name, 'to' => null],
                            'Delegaciones' => ['from' => implode(', ', $dealershipNames), 'to' => null],
                        ],
                        dealershipNames: $dealershipNames,
                    );

                    return (bool) $record->delete();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $dealershipIds = collect($data['dealership_ids'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $conflictingDealerships = ZoneResource::conflictingDealershipNames($dealershipIds->all(), $this->getRecord());

        if ($conflictingDealerships !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'dealership_ids' => 'Hay delegaciones que ya pertenecen a otra zona: ' . implode(', ', $conflictingDealerships) . '.',
            ]);
        }

        $this->pendingDealershipIds = $dealershipIds->all();
        $this->pendingActivityLogChanges = $this->buildChangeSet($this->getRecord(), $data, $this->pendingDealershipIds);

        unset($data['dealership_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        $service = app(ZoneManagementService::class);
        $service->syncDealershipAssignments($this->getRecord(), $this->pendingDealershipIds);

        if ($this->pendingActivityLogChanges !== []) {
            $dealershipNames = $service->dealershipNames($this->pendingDealershipIds);

            $service->recordActivityLog(
                actor: $actor,
                zone: $this->getRecord(),
                action: ZoneActivityLog::ACTION_UPDATED,
                changes: $this->pendingActivityLogChanges,
                dealershipNames: $dealershipNames,
            );
        }

        $this->pendingDealershipIds = [];
        $this->pendingActivityLogChanges = [];
    }

    protected function buildChangeSet(Zone $zone, array $newValues, array $newDealershipIds): array
    {
        $zone->loadMissing('dealerships');

        $currentDealershipNames = ZoneResource::dealershipNames($zone->dealerships->pluck('id')->all());
        $newDealershipNames = ZoneResource::dealershipNames($newDealershipIds);
        $changes = [];

        if ($zone->name !== ($newValues['name'] ?? $zone->name)) {
            $changes['Nombre'] = [
                'from' => $zone->name,
                'to' => $newValues['name'] ?? null,
            ];
        }

        $currentDealershipSummary = implode(', ', $currentDealershipNames);
        $newDealershipSummary = implode(', ', $newDealershipNames);

        if ($currentDealershipSummary !== $newDealershipSummary) {
            $changes['Delegaciones'] = [
                'from' => $currentDealershipSummary,
                'to' => $newDealershipSummary,
            ];
        }

        return $changes;
    }
}
