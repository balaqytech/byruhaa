<?php

namespace App\Modules\Store\Filament\Resources\Products\Pages;

use App\Modules\Store\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
