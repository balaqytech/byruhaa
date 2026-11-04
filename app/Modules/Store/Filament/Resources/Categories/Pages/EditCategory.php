<?php

namespace App\Modules\Store\Filament\Resources\Categories\Pages;

use App\Modules\Store\Actions\ValidateCategoryImage;
use App\Modules\Store\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(ValidateCategoryImage::class)->execute($data);
    }
}
