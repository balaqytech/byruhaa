<?php

namespace App\Filament\Resources\LedgerTransactions\Tables;

use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LedgerTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['source', 'entries.ledgerAccount'])
                ->latest('occurred_at'))
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('description')
                    ->label(__('admin.fields.description'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('source')
                    ->label(__('admin.fields.source'))
                    ->state(fn (LedgerTransaction $record): string => self::sourceLabel($record)),
                TextColumn::make('total_baisa')
                    ->label(__('admin.fields.total'))
                    ->formatStateUsing(fn (int $state, LedgerTransaction $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('occurred_at')
                    ->label(__('admin.fields.occurred_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('payments')
                    ->label(__('admin.resources.payments.plural_label'))
                    ->query(fn (Builder $query): Builder => $query->where('source_type', (new Payment)->getMorphClass())),
                Filter::make('refunds')
                    ->label(__('admin.resources.payment_refunds.plural_label'))
                    ->query(fn (Builder $query): Builder => $query->where('source_type', (new PaymentRefund)->getMorphClass())),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    private static function sourceLabel(LedgerTransaction $record): string
    {
        $source = $record->source;

        if ($source instanceof Payment || $source instanceof PaymentRefund) {
            return $source->reference;
        }

        return '-';
    }
}
