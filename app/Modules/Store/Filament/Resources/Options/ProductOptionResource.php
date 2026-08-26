<?php

namespace App\Modules\Store\Filament\Resources\Options;

use App\Modules\Store\Filament\Resources\Options\Pages\CreateProductOption;
use App\Modules\Store\Filament\Resources\Options\Pages\EditProductOption;
use App\Modules\Store\Filament\Resources\Options\Pages\ListProductOptions;
use App\Modules\Store\Filament\Resources\Options\RelationManagers\InventoryMovementsRelationManager;
use App\Modules\Store\Filament\Resources\Options\Schemas\ProductOptionForm;
use App\Modules\Store\Filament\Resources\Options\Tables\ProductOptionsTable;
use App\Modules\Store\Models\ProductOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use UnitEnum;

class ProductOptionResource extends Resource
{
    protected static ?string $model = ProductOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductOptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductOptionsTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.store_product_options.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.store_product_options.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.store_product_options.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.store');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductOptions::route('/'),
            'create' => CreateProductOption::route('/create'),
            'edit' => EditProductOption::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            InventoryMovementsRelationManager::class,
            AuditsRelationManager::class,
        ];
    }
}
