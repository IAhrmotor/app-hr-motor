<?php

namespace App\Filament\Resources\ChatGroups\Pages;

use App\Filament\Resources\ChatGroups\ChatGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListChatGroups extends ListRecords
{
    protected static string $resource = ChatGroupResource::class;
}
