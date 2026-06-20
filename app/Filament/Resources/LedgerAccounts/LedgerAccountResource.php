<?php

namespace App\Filament\Resources\LedgerAccounts;

use App\Filament\Resources\LedgerAccounts\Pages\ListLedgerAccounts;
use App\Filament\Resources\LedgerAccounts\Pages\ViewLedgerAccount;
use App\Filament\Resources\LedgerAccounts\Schemas\LedgerAccountInfolist;
use App\Filament\Resources\LedgerAccounts\Tables\LedgerAccountsTable;
use App\Models\LedgerAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LedgerAccountResource extends Resource
{
    protected static ?string $model = LedgerAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function getModelLabel(): string
    {
        return __('admin.resources.ledger_accounts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.ledger_accounts.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.ledger_accounts.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function infolist(Schema $schema): Schema
    {
        return LedgerAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LedgerAccountsTable::configure($table);
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
            'index' => ListLedgerAccounts::route('/'),
            'view' => ViewLedgerAccount::route('/{record}'),
        ];
    }
}
