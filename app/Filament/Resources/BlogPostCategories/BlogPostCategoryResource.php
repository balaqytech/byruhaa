<?php

namespace App\Filament\Resources\BlogPostCategories;

use App\Filament\Resources\BlogPostCategories\Pages\CreateBlogPostCategory;
use App\Filament\Resources\BlogPostCategories\Pages\EditBlogPostCategory;
use App\Filament\Resources\BlogPostCategories\Pages\ListBlogPostCategories;
use App\Filament\Resources\BlogPostCategories\Schemas\BlogPostCategoryForm;
use App\Filament\Resources\BlogPostCategories\Tables\BlogPostCategoriesTable;
use App\Modules\Content\Models\BlogPostCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BlogPostCategoryResource extends Resource
{
    protected static ?string $model = BlogPostCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    public static function form(Schema $schema): Schema
    {
        return BlogPostCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogPostCategoriesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.blog_post_categories.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.blog_post_categories.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.blog_post_categories.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.content');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPostCategories::route('/'),
            'create' => CreateBlogPostCategory::route('/create'),
            'edit' => EditBlogPostCategory::route('/{record}/edit'),
        ];
    }
}
