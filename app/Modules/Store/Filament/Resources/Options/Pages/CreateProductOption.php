<?php

namespace App\Modules\Store\Filament\Resources\Options\Pages;

use App\Modules\Store\Filament\Resources\Options\ProductOptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductOption extends CreateRecord
{
    protected static string $resource = ProductOptionResource::class;
}
