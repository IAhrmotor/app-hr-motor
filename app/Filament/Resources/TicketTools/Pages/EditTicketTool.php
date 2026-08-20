<?php

namespace App\Filament\Resources\TicketTools\Pages;

use App\Filament\Resources\TicketTools\TicketToolResource;
use App\Models\TicketTool;
use App\Models\TicketToolActivityLog;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicketTool extends EditRecord
{
    protected static string $resource = TicketToolResource::class;

    protected array $pendingActivityLogChanges = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar')
                ->using(function (TicketTool $record): bool {
                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        return false;
                    }

                    TicketToolResource::recordActivity(
                        actor: $actor,
                        tool: $record,
                        action: TicketToolActivityLog::ACTION_DELETED,
                        changes: [
                            'name' => ['from' => $record->name, 'to' => null],
                            'color' => ['from' => $record->color, 'to' => null],
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

        if ($actor instanceof User) {
            TicketToolResource::recordActivity(
                actor: $actor,
                tool: $this->getRecord(),
                action: TicketToolActivityLog::ACTION_UPDATED,
                changes: $this->pendingActivityLogChanges,
            );
        }

        $this->pendingActivityLogChanges = [];
    }

    protected function buildChangeSet(TicketTool $tool, array $newValues): array
    {
        $labels = [
            'name' => 'Nombre',
            'color' => 'Color',
        ];

        return collect($newValues)
            ->filter(fn ($value, $field) => $tool->{$field} !== $value)
            ->mapWithKeys(fn ($value, $field) => [
                $labels[$field] ?? $field => [
                    'from' => $tool->{$field},
                    'to' => $value,
                ],
            ])
            ->all();
    }
}
