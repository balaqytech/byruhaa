<?php

namespace App\Filament\Resources\BlogPostCategories\Pages;

use App\Filament\Resources\BlogPostCategories\BlogPostCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogPostCategories extends ListRecords
{
    protected static string $resource = BlogPostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
