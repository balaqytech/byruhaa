<?php

namespace App\Modules\Store\Filament\Resources\Options\Pages;

use App\Modules\Store\Filament\Resources\Options\ProductOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductOptions extends ListRecords
{
    protected static string $resource = ProductOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
