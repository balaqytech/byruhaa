<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Actions\RefundPayment;
use App\Enums\PaymentState;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Payment;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'bookingInstallment.paymentSchedule.booking.customer',
                    'bookingInstallment.paymentSchedule.booking.event',
                    'refunds',
                    'ledgerTransaction',
                ])
                ->latest('id'))
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('bookingInstallment.paymentSchedule.booking.reference')
                    ->label(__('admin.fields.booking'))
                    ->searchable()
                    ->url(fn (Payment $record): ?string => $record->bookingInstallment?->paymentSchedule?->booking
                        ? BookingResource::getUrl('view', ['record' => $record->bookingInstallment->paymentSchedule->booking])
                        : null),
                TextColumn::make('bookingInstallment.paymentSchedule.booking.customer.name')
                    ->label(__('admin.fields.customer'))
                    ->searchable(),
                TextColumn::make('bookingInstallment.paymentSchedule.booking.event.name')
                    ->label(__('admin.fields.event'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->searchable()
                    ->badge(),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->formatStateUsing(fn (int $state, Payment $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('refundable_amount_baisa')
                    ->label(__('admin.fields.refundable_amount'))
                    ->state(fn (Payment $record): int => $record->refundableAmountBaisa())
                    ->formatStateUsing(fn (int $state, Payment $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('state')
                    ->label(__('admin.fields.state'))
                    ->formatStateUsing(fn (PaymentState $state): string => $state->label())
                    ->badge()
                    ->sortable(),
                TextColumn::make('provider_payment_status')
                    ->label(__('admin.fields.provider_status'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label(__('admin.fields.paid_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('admin.fields.state'))
                    ->options(self::paymentStateOptions()),
                SelectFilter::make('provider')
                    ->label(__('admin.fields.provider'))
                    ->options([
                        'thawani' => 'Thawani',
                    ]),
                Filter::make('refundable')
                    ->label(__('admin.filters.refundable'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('state', [PaymentState::Paid->value, PaymentState::PartiallyRefunded->value])),
            ])
            ->recordActions([
                ViewAction::make(),
                self::refundAction(),
            ]);
    }

    public static function refundAction(): Action
    {
        return Action::make('refund')
            ->label(__('admin.actions.refund'))
            ->icon(Heroicon::OutlinedReceiptRefund)
            ->color('warning')
            ->visible(fn (Payment $record): bool => $record->isRefundable())
            ->modalHeading(__('admin.actions.refund_payment'))
            ->schema([
                TextInput::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn (Payment $record): int => $record->refundableAmountBaisa())
                    ->default(fn (Payment $record): int => $record->refundableAmountBaisa())
                    ->suffix('baisa'),
                Textarea::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->required()
                    ->maxLength(255)
                    ->default(__('admin.refunds.default_reason')),
            ])
            ->action(function (Payment $record, RefundPayment $refundPayment, array $data): void {
                try {
                    $refundPayment->execute(
                        $record,
                        (int) $data['amount_baisa'],
                        (string) $data['reason'],
                    );
                } catch (ValidationException $exception) {
                    throw $exception;
                }

                Notification::make()
                    ->title(__('admin.notifications.refund_processed'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, string>
     */
    private static function paymentStateOptions(): array
    {
        return collect(PaymentState::cases())
            ->mapWithKeys(fn (PaymentState $state): array => [$state->value => $state->label()])
            ->all();
    }
}
