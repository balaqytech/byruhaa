<?php

namespace App\Modules\Store\Filament\Resources\Orders\Schemas;

use App\Modules\Store\Models\Order;
use App\Modules\Store\Settings\StoreSettings;
use App\Modules\Store\States\Order\OrderState;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.store.sections.order'))
                ->columns(3)
                ->schema([
                    TextEntry::make('reference')->label(__('admin.fields.reference'))->copyable(),
                    TextEntry::make('status')
                        ->label(__('admin.fields.status'))
                        ->formatStateUsing(fn (OrderState $state): string => __('admin.store.order_statuses.'.$state->getValue()))
                        ->badge(),
                    TextEntry::make('total_baisa')
                        ->label(__('admin.fields.total_including_vat'))
                        ->state(fn (Order $record): string => MoneyFormatter::baisa($record->total_baisa, 'OMR')),
                    TextEntry::make('customer_name')->label(__('admin.fields.customer')),
                    TextEntry::make('pos_cashier_user_id')->label('معرّف كاشير نقطة البيع')->visible(fn (Order $record): bool => $record->pos_cashier_user_id !== null),
                    TextEntry::make('payment_method')
                        ->label(__('admin.store.admin_order.payment_method'))
                        ->formatStateUsing(fn (string $state): string => $state === 'wallet'
                            ? __('admin.store.admin_order.minor_wallet')
                            : ($state === 'cash' ? 'نقدًا' : __('admin.store.admin_order.direct_payment'))),
                    TextEntry::make('pickup_at')
                        ->label(__('admin.fields.pickup_at'))
                        ->dateTime()
                        ->placeholder(__('admin.store.pickup_types.immediate')),
                    TextEntry::make('payment_link')
                        ->label(__('admin.store.admin_order.payment_link'))
                        ->state(self::paymentLink(...))
                        ->copyable()
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->placeholder(__('admin.store.admin_order.payment_link_unavailable'))
                        ->visible(fn (Order $record): bool => $record->payment_method === 'thawani')
                        ->columnSpanFull(),
                ]),
            Tabs::make('order_details')
                ->tabs([
                    Tab::make(__('admin.store.sections.items'))
                        ->schema([
                            self::itemsSection(),
                            self::totalsSection(),
                        ]),
                    Tab::make(__('admin.store.order_tabs.customer_payment'))
                        ->schema([
                            self::customerSection(),
                            self::fulfillmentSection(),
                        ]),
                    Tab::make(__('admin.store.sections.status_history'))
                        ->schema([self::historySection()]),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function itemsSection(): Section
    {
        return Section::make(__('admin.store.sections.items'))
            ->schema([
                RepeatableEntry::make('items')
                    ->table([
                        TableColumn::make(__('admin.fields.product')),
                        TableColumn::make(__('admin.fields.option')),
                        TableColumn::make(__('admin.fields.sku')),
                        TableColumn::make(__('admin.fields.quantity')),
                        TableColumn::make(__('admin.fields.regular_unit_price')),
                        TableColumn::make(__('admin.fields.discount_amount')),
                        TableColumn::make(__('admin.fields.total')),
                        TableColumn::make(__('admin.fields.note')),
                    ])
                    ->schema([
                        TextEntry::make('product_name'),
                        TextEntry::make('option_name'),
                        TextEntry::make('sku'),
                        TextEntry::make('quantity'),
                        TextEntry::make('regular_unit_price_baisa')
                            ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                        TextEntry::make('unit_discount_baisa')
                            ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                        TextEntry::make('line_total_baisa')
                            ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                        TextEntry::make('note')->placeholder('-'),
                    ]),
            ]);
    }

    private static function totalsSection(): Section
    {
        return Section::make(__('admin.store.sections.totals'))
            ->columns(3)
            ->schema([
                TextEntry::make('pricing_tier')->label(__('admin.fields.pricing_tier'))->formatStateUsing(fn (string $state): string => __('admin.store.pricing_tiers.'.$state))->badge(),
                TextEntry::make('regular_total_baisa')->label(__('admin.fields.regular_total'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->regular_total_baisa, 'OMR')),
                TextEntry::make('discount_baisa')->label(__('admin.fields.discount_amount'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->discount_baisa, 'OMR')),
                TextEntry::make('subtotal_baisa')->label(__('admin.fields.subtotal_before_vat'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->subtotal_baisa, 'OMR')),
                TextEntry::make('vat_baisa')->label(__('admin.fields.vat_included'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->vat_baisa, 'OMR')),
                TextEntry::make('total_baisa')->label(__('admin.fields.total_including_vat'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->total_baisa, 'OMR')),
            ]);
    }

    private static function customerSection(): Section
    {
        return Section::make(__('admin.store.sections.customer_payment'))
            ->columns(3)
            ->schema([
                TextEntry::make('customer_name')->label(__('admin.fields.customer')),
                TextEntry::make('customer_phone')->label(__('admin.fields.phone_number'))->copyable(),
                TextEntry::make('customer_email')->label(__('admin.fields.email_address'))->placeholder('-')->copyable(),
                TextEntry::make('recipient_name')->label(__('admin.fields.recipient')),
                TextEntry::make('recipient_phone')->label(__('admin.fields.recipient_phone'))->copyable(),
                TextEntry::make('payment_method')
                    ->label(__('admin.store.admin_order.payment_method'))
                    ->formatStateUsing(fn (string $state): string => $state === 'wallet'
                        ? __('admin.store.admin_order.minor_wallet')
                        : ($state === 'cash' ? 'نقدًا' : __('admin.store.admin_order.direct_payment'))),
                TextEntry::make('cash_received_baisa')
                    ->label('المبلغ النقدي المستلم')
                    ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR'))
                    ->visible(fn (Order $record): bool => $record->payment_method === 'cash'),
                TextEntry::make('cash_change_baisa')
                    ->label('الباقي النقدي')
                    ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR'))
                    ->visible(fn (Order $record): bool => $record->payment_method === 'cash'),
                TextEntry::make('payment_reference')->label(__('admin.store.admin_order.payment_reference'))->placeholder('-')->copyable(),
                TextEntry::make('paid_at')->label(__('admin.store.admin_order.paid_at'))->dateTime()->placeholder('-'),
            ]);
    }

    private static function fulfillmentSection(): Section
    {
        return Section::make(__('admin.store.sections.receipt_pickup'))
            ->columns(2)
            ->schema([
                TextEntry::make('pickup_type')
                    ->label(__('admin.store.admin_order.pickup_type'))
                    ->formatStateUsing(fn (string $state): string => __('admin.store.pickup_types.'.$state)),
                TextEntry::make('pickup_at')->label(__('admin.fields.pickup_at'))->dateTime()->placeholder(__('admin.store.pickup_types.immediate')),
                TextEntry::make('note')->label(__('admin.fields.note'))->placeholder('-')->columnSpanFull(),
            ]);
    }

    private static function historySection(): Section
    {
        return Section::make(__('admin.store.sections.status_history'))
            ->schema([
                RepeatableEntry::make('statusHistory')
                    ->table([
                        TableColumn::make(__('admin.fields.from')),
                        TableColumn::make(__('admin.fields.to')),
                        TableColumn::make(__('admin.fields.when')),
                        TableColumn::make(__('admin.fields.note')),
                    ])
                    ->schema([
                        TextEntry::make('from_status')
                            ->formatStateUsing(fn (?string $state): string => $state ? __('admin.store.order_statuses.'.$state) : '-')
                            ->placeholder('-'),
                        TextEntry::make('to_status')
                            ->formatStateUsing(fn (string $state): string => __('admin.store.order_statuses.'.$state)),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('note')->placeholder('-'),
                    ]),
            ]);
    }

    private static function paymentLink(Order $record): ?string
    {
        if ($record->payment_method !== 'thawani' || $record->status->getValue() !== 'pending_payment') {
            return null;
        }

        $expiresAt = $record->created_at?->copy()->addMinutes(max(1, app(StoreSettings::class)->reservation_duration_minutes));

        if ($expiresAt === null || $expiresAt->isPast()) {
            return null;
        }

        return URL::temporarySignedRoute('store.orders.payment.link', $expiresAt, ['order' => $record->payment_token]);
    }
}
