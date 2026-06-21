<?php

namespace App\Filament\Resources\AffiliateCommissions;

use App\Filament\Resources\AffiliateCommissions\Pages\ListAffiliateCommissions;
use App\Filament\Resources\AffiliateCommissions\Pages\ViewAffiliateCommission;
use App\Filament\Resources\AffiliateCommissions\Schemas\AffiliateCommissionInfolist;
use App\Filament\Resources\AffiliateCommissions\Tables\AffiliateCommissionsTable;
use App\Models\AffiliateCommission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AffiliateCommissionResource extends Resource
{
    protected static ?string $model = AffiliateCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getModelLabel(): string
    {
        return __('admin.resources.affiliate_commissions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.affiliate_commissions.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.affiliate_commissions.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function infolist(Schema $schema): Schema
    {
        return AffiliateCommissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AffiliateCommissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliateCommissions::route('/'),
            'view' => ViewAffiliateCommission::route('/{record}'),
        ];
    }
}
