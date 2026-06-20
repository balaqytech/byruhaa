<?php

namespace App\Filament\Resources\PaymentRefunds\Schemas;

use App\Enums\PaymentRefundState;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\PaymentRefund;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class PaymentRefundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.payment_refunds.label'))
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.resources.payment_refunds.label'))
                            ->schema([
                                Section::make(__('admin.resources.payment_refunds.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('reference')
                                            ->label(__('admin.fields.reference'))
                                            ->copyable(),
                                        TextEntry::make('state')
                                            ->label(__('admin.fields.state'))
                                            ->formatStateUsing(fn (PaymentRefundState $state): string => $state->label())
                                            ->badge(),
                                        TextEntry::make('amount_baisa')
                                            ->label(__('admin.fields.amount'))
                                            ->state(fn (PaymentRefund $record): string => MoneyFormatter::baisa($record->amount_baisa, $record->currency)),
                                        TextEntry::make('reason')
                                            ->label(__('admin.fields.reason'))
                                            ->columnSpanFull(),
                                        TextEntry::make('provider_refund_id')
                                            ->label(__('admin.fields.provider_refund_id'))
                                            ->copyable()
                                            ->placeholder('-'),
                                        TextEntry::make('provider_payment_id')
                                            ->label(__('admin.fields.provider_payment_id'))
                                            ->copyable()
                                            ->placeholder('-'),
                                        TextEntry::make('provider_status')
                                            ->label(__('admin.fields.provider_status'))
                                            ->placeholder('-'),
                                        TextEntry::make('processed_at')
                                            ->label(__('admin.fields.processed_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                    ]),
                            ]),
                        Tab::make(__('admin.resources.payments.label'))
                            ->schema([
                                Section::make(__('admin.resources.payments.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('payment.reference')
                                            ->label(__('admin.resources.payments.label'))
                                            ->url(fn (PaymentRefund $record): string => PaymentResource::getUrl('view', ['record' => $record->payment])),
                                        TextEntry::make('payment.bookingInstallment.paymentSchedule.booking.reference')
                                            ->label(__('admin.fields.booking')),
                                        TextEntry::make('payment.bookingInstallment.paymentSchedule.booking.customer.name')
                                            ->label(__('admin.fields.customer')),
                                    ]),
                            ]),
                        Tab::make(__('admin.resources.ledger_transactions.label'))
                            ->schema([
                                Section::make(__('admin.resources.ledger_transactions.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('ledgerTransaction.reference')
                                            ->label(__('admin.fields.reference'))
                                            ->url(fn (PaymentRefund $record): ?string => $record->ledgerTransaction
                                                ? LedgerTransactionResource::getUrl('view', ['record' => $record->ledgerTransaction])
                                                : null)
                                            ->placeholder('-'),
                                        TextEntry::make('ledgerTransaction.description')
                                            ->label(__('admin.fields.description'))
                                            ->placeholder('-'),
                                        TextEntry::make('ledgerTransaction.total_baisa')
                                            ->label(__('admin.fields.total'))
                                            ->state(fn (PaymentRefund $record): string => $record->ledgerTransaction
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
                                            ->state(fn (PaymentRefund $record): array => self::payload($record->request_payload))
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('admin.fields.response_payload'))
                                    ->schema([
                                        KeyValueEntry::make('response_payload')
                                            ->label(__('admin.fields.response_payload'))
                                            ->state(fn (PaymentRefund $record): array => self::payload($record->response_payload))
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
