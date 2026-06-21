<?php

namespace App\Filament\Resources\PaymentRefunds\Tables;

use App\Enums\PaymentRefundState;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\PaymentRefund;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentRefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'payment.bookingInstallment.paymentSchedule.booking.customer',
                    'ledgerTransaction',
                ])
                ->latest('id'))
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('payment.reference')
                    ->label(__('admin.resources.payments.label'))
                    ->searchable()
                    ->url(fn (PaymentRefund $record): string => PaymentResource::getUrl('view', ['record' => $record->payment])),
                TextColumn::make('payment.bookingInstallment.paymentSchedule.booking.reference')
                    ->label(__('admin.fields.booking'))
                    ->searchable(),
                TextColumn::make('payment.bookingInstallment.paymentSchedule.booking.customer.name')
                    ->label(__('admin.fields.customer'))
                    ->searchable(),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->formatStateUsing(fn (int $state, PaymentRefund $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('state')
                    ->label(__('admin.fields.state'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('provider_status')
                    ->label(__('admin.fields.provider_status'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('processed_at')
                    ->label(__('admin.fields.processed_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('admin.fields.state'))
                    ->options(PaymentRefundState::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
