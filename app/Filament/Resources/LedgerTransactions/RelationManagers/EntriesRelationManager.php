<?php

namespace App\Filament\Resources\LedgerTransactions\RelationManagers;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use App\Modules\Finance\Models\LedgerEntry;
use App\Support\MoneyFormatter;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.fields.entries');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ledgerAccount.name')
            ->columns([
                TextColumn::make('ledgerAccount.code')
                    ->label(__('admin.fields.code'))
                    ->searchable()
                    ->url(fn (LedgerEntry $record): string => LedgerAccountResource::getUrl('view', ['record' => $record->ledgerAccount])),
                TextColumn::make('ledgerAccount.name')
                    ->label(__('admin.resources.ledger_accounts.label'))
                    ->searchable(),
                TextColumn::make('debit_baisa')
                    ->label(__('admin.fields.debit'))
                    ->formatStateUsing(fn (int $state, LedgerEntry $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('credit_baisa')
                    ->label(__('admin.fields.credit'))
                    ->formatStateUsing(fn (int $state, LedgerEntry $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('memo')
                    ->label(__('admin.fields.memo'))
                    ->limit(50)
                    ->placeholder('-'),
            ]);
    }
}
