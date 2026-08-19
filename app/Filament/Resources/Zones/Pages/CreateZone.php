<?php

namespace App\Filament\Resources\Zones\Pages;

use App\Filament\Resources\Zones\ZoneResource;
use App\Models\User;
use App\Models\ZoneActivityLog;
use App\Services\ZoneManagementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateZone extends CreateRecord
{
    protected static string $resource = ZoneResource::class;

    protected array $pendingDealershipIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $dealershipIds = collect($data['dealership_ids'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $conflictingDealerships = ZoneResource::conflictingDealershipNames($dealershipIds->all());

        if ($conflictingDealerships !== []) {
            throw ValidationException::withMessages([
                'dealership_ids' => 'Hay delegaciones que ya pertenecen a otra zona: ' . implode(', ', $conflictingDealerships) . '.',
            ]);
        }

        $this->pendingDealershipIds = $dealershipIds->all();

        unset($data['dealership_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        $service = app(ZoneManagementService::class);
        $service->syncDealershipAssignments($this->record, $this->pendingDealershipIds);

        $dealershipNames = $service->dealershipNames($this->pendingDealershipIds);

        $service->recordActivityLog(
            actor: $actor,
            zone: $this->record,
            action: ZoneActivityLog::ACTION_CREATED,
            changes: [
                'Nombre' => ['from' => null, 'to' => $this->record->name],
                'Delegaciones' => ['from' => null, 'to' => implode(', ', $dealershipNames)],
            ],
            dealershipNames: $dealershipNames,
        );

        $this->pendingDealershipIds = [];
    }
}
