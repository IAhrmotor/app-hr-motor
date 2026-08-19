<?php

namespace App\Filament\Resources\Dealerships\Pages;

use App\Filament\Resources\Dealerships\DealershipResource;
use App\Models\DealershipActivityLog;
use App\Models\User;
use App\Services\DealershipActivityLogWriter;
use Filament\Resources\Pages\CreateRecord;

class CreateDealership extends CreateRecord
{
    protected static string $resource = DealershipResource::class;

    protected function afterCreate(): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        app(DealershipActivityLogWriter::class)->record(
            actor: $actor,
            dealership: $this->record,
            action: DealershipActivityLog::ACTION_CREATED,
        );
    }
}
