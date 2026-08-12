<?php

namespace App\Modules\Store\Filament\Resources\Categories;

use App\Modules\Store\Filament\Resources\Categories\Pages\CreateCategory;
use App\Modules\Store\Filament\Resources\Categories\Pages\EditCategory;
use App\Modules\Store\Filament\Resources\Categories\Pages\ListCategories;
use App\Modules\Store\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Modules\Store\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Modules\Store\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.store_categories.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.store_categories.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.store_categories.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.store');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
