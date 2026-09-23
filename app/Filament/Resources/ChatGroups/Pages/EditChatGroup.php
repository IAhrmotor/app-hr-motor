<?php

namespace App\Filament\Resources\ChatGroups\Pages;

use App\Filament\Resources\ChatGroups\ChatGroupResource;
use App\Models\CompanyChatGroup;
use App\Models\User;
use App\Services\CompanyChatGroupManagementService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditChatGroup extends EditRecord
{
    protected static string $resource = ChatGroupResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('participants');
        $data['participant_ids'] = $this->record->participants->pluck('id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && ChatGroupResource::canAccess(), 403);

        $ids = app(CompanyChatGroupManagementService::class)->normalizeParticipantIds($data['participant_ids'] ?? []);
        $updated = app(CompanyChatGroupManagementService::class)->update($record, $data['name'], $ids->all(), $actor);
        Notification::make()->success()->title('Grupo actualizado correctamente.')->send();

        return $updated;
    }

    protected function authorizeAccess(): void
    {
        abort_unless(ChatGroupResource::canAccess(), 403);
        parent::authorizeAccess();
    }

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()->label('Borrar')->using(function (CompanyChatGroup $record): bool {
            $actor = auth()->user();
            abort_unless($actor instanceof User && ChatGroupResource::canAccess(), 403);
            app(CompanyChatGroupManagementService::class)->delete($record, $actor);

            return true;
        })];
    }
}
