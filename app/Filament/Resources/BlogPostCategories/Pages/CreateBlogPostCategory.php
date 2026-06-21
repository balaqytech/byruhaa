<?php

namespace App\Filament\Resources\BlogPostCategories\Pages;

use App\Filament\Resources\BlogPostCategories\BlogPostCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPostCategory extends CreateRecord
{
    protected static string $resource = BlogPostCategoryResource::class;
}
