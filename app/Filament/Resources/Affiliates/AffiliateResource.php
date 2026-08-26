<?php

namespace App\Filament\Resources\Affiliates;

use App\Filament\Resources\Affiliates\Pages\CreateAffiliate;
use App\Filament\Resources\Affiliates\Pages\EditAffiliate;
use App\Filament\Resources\Affiliates\Pages\ListAffiliates;
use App\Filament\Resources\Affiliates\Pages\ViewAffiliate;
use App\Filament\Resources\Affiliates\RelationManagers\CommissionsRelationManager;
use App\Filament\Resources\Affiliates\RelationManagers\PayoutRequestsRelationManager;
use App\Filament\Resources\Affiliates\RelationManagers\ReferralsRelationManager;
use App\Filament\Resources\Affiliates\Schemas\AffiliateForm;
use App\Filament\Resources\Affiliates\Schemas\AffiliateInfolist;
use App\Filament\Resources\Affiliates\Tables\AffiliatesTable;
use App\Modules\Affiliates\Models\Affiliate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use UnitEnum;

class AffiliateResource extends Resource
{
    protected static ?string $model = Affiliate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('admin.resources.affiliates.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.affiliates.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.affiliates.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.customer_management');
    }

    public static function form(Schema $schema): Schema
    {
        return AffiliateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AffiliateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AffiliatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReferralsRelationManager::class,
            CommissionsRelationManager::class,
            PayoutRequestsRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliates::route('/'),
            'create' => CreateAffiliate::route('/create'),
            'view' => ViewAffiliate::route('/{record}'),
            'edit' => EditAffiliate::route('/{record}/edit'),
        ];
    }
}
