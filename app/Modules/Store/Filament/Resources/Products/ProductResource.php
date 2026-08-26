<?php

namespace App\Modules\Store\Filament\Resources\Products;

use App\Modules\Store\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Store\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Store\Filament\Resources\Products\Pages\ListProducts;
use App\Modules\Store\Filament\Resources\Products\Schemas\ProductForm;
use App\Modules\Store\Filament\Resources\Products\Tables\ProductsTable;
use App\Modules\Store\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.store_products.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.store_products.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.store_products.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.store');
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
