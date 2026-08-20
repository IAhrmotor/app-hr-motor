<?php

namespace App\Filament\Resources\TicketTools\Pages;

use App\Filament\Resources\TicketTools\TicketToolResource;
use App\Models\TicketTool;
use App\Models\TicketToolActivityLog;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketTool extends CreateRecord
{
    protected static string $resource = TicketToolResource::class;

    protected function afterCreate(): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        /** @var TicketTool $tool */
        $tool = $this->record;

        TicketToolResource::recordActivity(
            actor: $actor,
            tool: $tool,
            action: TicketToolActivityLog::ACTION_CREATED,
            changes: [
                'name' => ['from' => null, 'to' => $tool->name],
                'color' => ['from' => null, 'to' => $tool->color],
            ],
        );
    }
}
