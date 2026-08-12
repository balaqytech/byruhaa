<?php

namespace App\Modules\Store\Filament\Resources\Categories\Pages;

use App\Modules\Store\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;
}
