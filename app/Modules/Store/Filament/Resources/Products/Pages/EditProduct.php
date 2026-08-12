<?php

namespace App\Modules\Store\Filament\Resources\Products\Pages;

use App\Modules\Store\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
}
