<?php

namespace App\Filament\Resources\Wallets;

use App\Enums\UserRole;
use App\Filament\Resources\Wallets\Pages\ListWallets;
use App\Filament\Resources\Wallets\Pages\ViewWallet;
use App\Filament\Resources\Wallets\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\Wallets\RelationManagers\TopUpsRelationManager;
use App\Filament\Resources\Wallets\Schemas\WalletInfolist;
use App\Filament\Resources\Wallets\Tables\WalletsTable;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Identity\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** @extends resource<Wallet> */
class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    public static function getModelLabel(): string
    {
        return __('admin_wallets.wallet');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin_wallets.wallets');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** @return Builder<Wallet> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('minorProfile.familyMember.customer');
    }

    public static function infolist(Schema $schema): Schema
    {
        return WalletInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WalletsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [MovementsRelationManager::class, TopUpsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListWallets::route('/'), 'view' => ViewWallet::route('/{record}')];
    }
}
