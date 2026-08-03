<?php

namespace App\Filament\Resources\LedgerTransactions;

use App\Filament\Resources\LedgerTransactions\Pages\ListLedgerTransactions;
use App\Filament\Resources\LedgerTransactions\Pages\ViewLedgerTransaction;
use App\Filament\Resources\LedgerTransactions\RelationManagers\EntriesRelationManager;
use App\Filament\Resources\LedgerTransactions\Schemas\LedgerTransactionInfolist;
use App\Filament\Resources\LedgerTransactions\Tables\LedgerTransactionsTable;
use App\Modules\Finance\Models\LedgerTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LedgerTransactionResource extends Resource
{
    protected static ?string $model = LedgerTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    public static function getModelLabel(): string
    {
        return __('admin.resources.ledger_transactions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.ledger_transactions.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.ledger_transactions.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function infolist(Schema $schema): Schema
    {
        return LedgerTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LedgerTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerTransactions::route('/'),
            'view' => ViewLedgerTransaction::route('/{record}'),
        ];
    }
}
