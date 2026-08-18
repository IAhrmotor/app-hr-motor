<?php

namespace App\Filament\Resources\Dealerships\Pages;

use App\Filament\Resources\Dealerships\DealershipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDealership extends EditRecord
{
    protected static string $resource = DealershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar')
                ->disabled(fn (): bool => $this->getRecord()->users()->exists())
                ->tooltip(fn (): ?string => $this->getRecord()->users()->exists()
                    ? 'No puedes eliminar una delegación con usuarios asignados.'
                    : 'Eliminar delegación'),
        ];
    }
}
