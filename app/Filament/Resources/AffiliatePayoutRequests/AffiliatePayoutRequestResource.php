<?php

namespace App\Filament\Resources\AffiliatePayoutRequests;

use App\Filament\Resources\AffiliatePayoutRequests\Pages\ListAffiliatePayoutRequests;
use App\Filament\Resources\AffiliatePayoutRequests\Pages\ViewAffiliatePayoutRequest;
use App\Filament\Resources\AffiliatePayoutRequests\Schemas\AffiliatePayoutRequestForm;
use App\Filament\Resources\AffiliatePayoutRequests\Schemas\AffiliatePayoutRequestInfolist;
use App\Filament\Resources\AffiliatePayoutRequests\Tables\AffiliatePayoutRequestsTable;
use App\Models\AffiliatePayoutRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AffiliatePayoutRequestResource extends Resource
{
    protected static ?string $model = AffiliatePayoutRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getModelLabel(): string
    {
        return __('admin.resources.affiliate_payout_requests.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.affiliate_payout_requests.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.affiliate_payout_requests.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function form(Schema $schema): Schema
    {
        return AffiliatePayoutRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AffiliatePayoutRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AffiliatePayoutRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliatePayoutRequests::route('/'),
            'view' => ViewAffiliatePayoutRequest::route('/{record}'),
        ];
    }
}
