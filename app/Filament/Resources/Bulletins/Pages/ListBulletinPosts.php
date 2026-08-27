<?php

namespace App\Filament\Resources\Bulletins\Pages;

use App\Filament\Resources\Bulletins\BulletinPostResource;
use Filament\Resources\Pages\ListRecords;

class ListBulletinPosts extends ListRecords
{
    protected static string $resource = BulletinPostResource::class;
}
