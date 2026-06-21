<?php

namespace App\Filament\Resources\LedgerAccounts\RelationManagers;

use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
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
            ->recordTitleAttribute('ledgerTransaction.reference')
            ->columns([
                TextColumn::make('ledgerTransaction.reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->url(fn (LedgerEntry $record): string => LedgerTransactionResource::getUrl('view', ['record' => $record->ledgerTransaction])),
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
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime(),
            ]);
    }
}
