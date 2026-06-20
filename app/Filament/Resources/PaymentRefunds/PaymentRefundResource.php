<?php

namespace App\Filament\Resources\PaymentRefunds;

use App\Filament\Resources\PaymentRefunds\Pages\ListPaymentRefunds;
use App\Filament\Resources\PaymentRefunds\Pages\ViewPaymentRefund;
use App\Filament\Resources\PaymentRefunds\Schemas\PaymentRefundInfolist;
use App\Filament\Resources\PaymentRefunds\Tables\PaymentRefundsTable;
use App\Models\PaymentRefund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentRefundResource extends Resource
{
    protected static ?string $model = PaymentRefund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    public static function getModelLabel(): string
    {
        return __('admin.resources.payment_refunds.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.payment_refunds.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.payment_refunds.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentRefundInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentRefundsTable::configure($table);
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
            'index' => ListPaymentRefunds::route('/'),
            'view' => ViewPaymentRefund::route('/{record}'),
        ];
    }
}
