<?php

namespace App\Filament\Resources\LedgerTransactions\RelationManagers;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use App\Models\LedgerEntry;
use App\Support\MoneyFormatter;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $title = 'Entries';

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
                    ->label('Debit')
                    ->formatStateUsing(fn (int $state, LedgerEntry $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('credit_baisa')
                    ->label('Credit')
                    ->formatStateUsing(fn (int $state, LedgerEntry $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('memo')
                    ->label('Memo')
                    ->limit(50)
                    ->placeholder('-'),
            ]);
    }
}
