<?php

namespace App\Modules\Store\Filament\Resources\Products\Pages;

use App\Modules\Store\Actions\EnsureDefaultProductOption;
use App\Modules\Store\Actions\PublishProduct;
use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Filament\Resources\Products\ProductResource;
use App\Modules\Store\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof Product) {
            return;
        }

        app(EnsureDefaultProductOption::class)->execute($record);

        if ($record->status === ProductStatus::Active) {
            app(PublishProduct::class)->execute($record);
        }
    }
}
