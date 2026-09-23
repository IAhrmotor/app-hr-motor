<?php

namespace App\Filament\Resources\ChatGroups\Pages;

use App\Filament\Resources\ChatGroups\ChatGroupResource;
use App\Models\User;
use App\Services\CompanyChatGroupManagementService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateChatGroup extends CreateRecord
{
    protected static string $resource = ChatGroupResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && ChatGroupResource::canAccess(), 403);

        $ids = app(CompanyChatGroupManagementService::class)->normalizeParticipantIds($data['participant_ids'] ?? []);
        unset($data['participant_ids']);
        $record = app(CompanyChatGroupManagementService::class)->create($data['name'], $ids->all(), $actor);
        Notification::make()->success()->title('Grupo creado correctamente.')->send();

        return $record;
    }
}
