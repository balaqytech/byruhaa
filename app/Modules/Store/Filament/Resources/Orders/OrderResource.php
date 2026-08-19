<?php

namespace App\Modules\Store\Filament\Resources\Orders;

use App\Modules\Store\Filament\Resources\Orders\Pages\ListOrders;
use App\Modules\Store\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Store\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Modules\Store\Filament\Resources\Orders\Tables\OrdersTable;
use App\Modules\Store\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.store_orders.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.store_orders.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.store_orders.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.store');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
