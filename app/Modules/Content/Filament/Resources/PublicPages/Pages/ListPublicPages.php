<?php

namespace App\Modules\Content\Filament\Resources\PublicPages\Pages;

use App\Modules\Content\Filament\Resources\PublicPages\PublicPageResource;
use Filament\Resources\Pages\ListRecords;

class ListPublicPages extends ListRecords
{
    protected static string $resource = PublicPageResource::class;
}
