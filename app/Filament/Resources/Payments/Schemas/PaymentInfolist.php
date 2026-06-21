<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Models\Payment;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.payments.label'))
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.resources.payments.label'))
                            ->schema([
                                Section::make(__('admin.resources.payments.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('reference')
                                            ->label(__('admin.fields.reference'))
                                            ->copyable(),
                                        TextEntry::make('state')
                                            ->label(__('admin.fields.state'))
                                            ->badge(),
                                        TextEntry::make('provider')
                                            ->label(__('admin.fields.provider'))
                                            ->badge(),
                                        TextEntry::make('amount_baisa')
                                            ->label(__('admin.fields.amount'))
                                            ->state(fn (Payment $record): string => MoneyFormatter::baisa($record->amount_baisa, $record->currency)),
                                        TextEntry::make('refundable_amount_baisa')
                                            ->label(__('admin.fields.refundable_amount'))
                                            ->state(fn (Payment $record): string => MoneyFormatter::baisa($record->refundableAmountBaisa(), $record->currency)),
                                        TextEntry::make('currency')
                                            ->label(__('admin.fields.currency')),
                                        TextEntry::make('provider_session_id')
                                            ->label(__('admin.fields.provider_session_id'))
                                            ->copyable()
                                            ->placeholder('-'),
                                        TextEntry::make('provider_payment_id')
                                            ->label(__('admin.fields.provider_payment_id'))
                                            ->copyable()
                                            ->placeholder('-'),
                                        TextEntry::make('provider_invoice')
                                            ->label(__('admin.fields.provider_invoice'))
                                            ->copyable()
                                            ->placeholder('-'),
                                        TextEntry::make('provider_payment_status')
                                            ->label(__('admin.fields.provider_status'))
                                            ->placeholder('-'),
                                        TextEntry::make('verified_at')
                                            ->label(__('admin.fields.verified_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('paid_at')
                                            ->label(__('admin.fields.paid_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                    ]),
                            ]),
                        Tab::make(__('admin.fields.booking'))
                            ->schema([
                                Section::make(__('admin.fields.booking'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('bookingInstallment.paymentSchedule.booking.reference')
                                            ->label(__('admin.fields.booking'))
                                            ->url(fn (Payment $record): ?string => $record->bookingInstallment?->paymentSchedule?->booking
                                                ? BookingResource::getUrl('view', ['record' => $record->bookingInstallment->paymentSchedule->booking])
                                                : null),
                                        TextEntry::make('bookingInstallment.paymentSchedule.booking.customer.name')
                                            ->label(__('admin.fields.customer')),
                                        TextEntry::make('bookingInstallment.paymentSchedule.booking.event.name')
                                            ->label(__('admin.fields.event')),
                                        TextEntry::make('bookingInstallment.name')
                                            ->label(__('ui.payments.installment'))
                                            ->placeholder('-'),
                                        TextEntry::make('bookingInstallment.due_date')
                                            ->label(__('admin.fields.due_date'))
                                            ->date(),
                                        TextEntry::make('bookingInstallment.state')
                                            ->label(__('admin.fields.installment_state'))
                                            ->badge(),
                                    ]),
                            ]),
                        Tab::make(__('admin.resources.ledger_transactions.label'))
                            ->schema([
                                Section::make(__('admin.resources.ledger_transactions.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('ledgerTransaction.reference')
                                            ->label(__('admin.fields.reference'))
                                            ->url(fn (Payment $record): ?string => $record->ledgerTransaction
                                                ? LedgerTransactionResource::getUrl('view', ['record' => $record->ledgerTransaction])
                                                : null)
                                            ->placeholder('-'),
                                        TextEntry::make('ledgerTransaction.description')
                                            ->label(__('admin.fields.description'))
                                            ->placeholder('-'),
                                        TextEntry::make('ledgerTransaction.total_baisa')
                                            ->label(__('admin.fields.total'))
                                            ->state(fn (Payment $record): string => $record->ledgerTransaction
                                                ? MoneyFormatter::baisa($record->ledgerTransaction->total_baisa, $record->ledgerTransaction->currency)
                                                : '-'),
                                    ]),
                            ]),
                        Tab::make(__('admin.fields.payloads'))
                            ->schema([
                                Section::make(__('admin.fields.request_payload'))
                                    ->schema([
                                        KeyValueEntry::make('request_payload')
                                            ->label(__('admin.fields.request_payload'))
                                            ->state(fn (Payment $record): array => self::payload($record->request_payload))
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('admin.fields.response_payload'))
                                    ->schema([
                                        KeyValueEntry::make('response_payload')
                                            ->label(__('admin.fields.response_payload'))
                                            ->state(fn (Payment $record): array => self::payload($record->response_payload))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, string>
     */
    private static function payload(?array $payload): array
    {
        return collect(Arr::dot($payload ?? []))
            ->map(fn (mixed $value): string => self::stringValue($value))
            ->all();
    }

    private static function stringValue(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '',
        };
    }
}
