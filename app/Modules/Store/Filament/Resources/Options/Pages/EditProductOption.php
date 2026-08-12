<?php

namespace App\Modules\Store\Filament\Resources\Options\Pages;

use App\Modules\Store\Filament\Resources\Options\ProductOptionResource;
use Filament\Resources\Pages\EditRecord;

class EditProductOption extends EditRecord
{
    protected static string $resource = ProductOptionResource::class;
}
