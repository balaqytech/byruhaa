<?php

namespace App\Filament\Resources\BlogPostCategories\Pages;

use App\Filament\Resources\BlogPostCategories\BlogPostCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPostCategory extends EditRecord
{
    protected static string $resource = BlogPostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
