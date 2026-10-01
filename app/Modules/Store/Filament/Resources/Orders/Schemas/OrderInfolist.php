<?php

namespace App\Modules\Store\Filament\Resources\Orders\Schemas;

use App\Modules\Store\Models\Order;
use App\Modules\Store\Settings\StoreSettings;
use App\Modules\Store\States\Order\OrderState;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.store.sections.order'))->columns(3)->schema([
                TextEntry::make('reference')->label(__('admin.fields.reference'))->copyable(),
                TextEntry::make('status')
                    ->label(__('admin.fields.status'))
                    ->formatStateUsing(fn (OrderState $state): string => __('admin.store.order_statuses.'.$state->getValue()))
                    ->badge(),
                TextEntry::make('pickup_at')->label(__('admin.fields.pickup_at'))->dateTime()->placeholder(__('admin.store.pickup_types.immediate')),
                TextEntry::make('customer_name')->label(__('admin.fields.customer')),
                TextEntry::make('customer_phone')->label(__('admin.fields.phone_number')),
                TextEntry::make('customer_email')->label(__('admin.fields.email_address'))->placeholder('-'),
                TextEntry::make('payment_method')
                    ->label(__('admin.store.admin_order.payment_method'))
                    ->formatStateUsing(fn (string $state): string => $state === 'wallet'
                        ? __('admin.store.admin_order.minor_wallet')
                        : __('admin.store.admin_order.direct_payment')),
                TextEntry::make('recipient_name')->label(__('admin.fields.recipient')),
                TextEntry::make('recipient_phone')->label(__('admin.fields.recipient_phone')),
                TextEntry::make('payment_link')
                    ->label(__('admin.store.admin_order.payment_link'))
                    ->state(function (Order $record): ?string {
                        if ($record->payment_method !== 'thawani' || $record->status->getValue() !== 'pending_payment') {
                            return null;
                        }

                        $expiresAt = $record->created_at?->copy()->addMinutes(max(1, app(StoreSettings::class)->reservation_duration_minutes));

                        if ($expiresAt === null || $expiresAt->isPast()) {
                            return null;
                        }

                        return URL::temporarySignedRoute('store.orders.payment.link', $expiresAt, ['order' => $record->payment_token]);
                    })
                    ->copyable()
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab()
                    ->placeholder(__('admin.store.admin_order.payment_link_unavailable'))
                    ->columnSpanFull(),
                TextEntry::make('note')->label(__('admin.fields.note'))->placeholder('-')->columnSpanFull(),
            ]),
            Section::make(__('admin.store.sections.totals'))->columns(3)->schema([
                TextEntry::make('pricing_tier')->label(__('admin.fields.pricing_tier'))->formatStateUsing(fn (string $state): string => __('admin.store.pricing_tiers.'.$state))->badge(),
                TextEntry::make('regular_total_baisa')->label(__('admin.fields.regular_total'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->regular_total_baisa, 'OMR')),
                TextEntry::make('discount_baisa')->label(__('admin.fields.discount_amount'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->discount_baisa, 'OMR')),
                TextEntry::make('subtotal_baisa')->label(__('admin.fields.subtotal_before_vat'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->subtotal_baisa, 'OMR')),
                TextEntry::make('vat_baisa')->label(__('admin.fields.vat_included'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->vat_baisa, 'OMR')),
                TextEntry::make('total_baisa')->label(__('admin.fields.total_including_vat'))->state(fn (Order $record): string => MoneyFormatter::baisa($record->total_baisa, 'OMR')),
            ]),
            Section::make(__('admin.store.sections.items'))->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('product_name')->label(__('admin.fields.product')),
                    TextEntry::make('option_name')->label(__('admin.fields.option')),
                    TextEntry::make('sku')->label(__('admin.fields.sku')),
                    TextEntry::make('quantity')->label(__('admin.fields.quantity')),
                    TextEntry::make('regular_unit_price_baisa')
                        ->label(__('admin.fields.regular_unit_price'))
                        ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                    TextEntry::make('unit_discount_baisa')
                        ->label(__('admin.fields.discount_amount'))
                        ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                    TextEntry::make('line_total_baisa')
                        ->label(__('admin.fields.total'))
                        ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                    TextEntry::make('note')->label(__('admin.fields.note'))->placeholder('-'),
                ])->columns(4),
            ]),
            Section::make(__('admin.store.sections.status_history'))->schema([
                RepeatableEntry::make('statusHistory')->schema([
                    TextEntry::make('from_status')
                        ->label(__('admin.fields.from'))
                        ->formatStateUsing(fn (?string $state): string => $state ? __('admin.store.order_statuses.'.$state) : '-')
                        ->placeholder('-'),
                    TextEntry::make('to_status')
                        ->label(__('admin.fields.to'))
                        ->formatStateUsing(fn (string $state): string => __('admin.store.order_statuses.'.$state)),
                    TextEntry::make('created_at')->label(__('admin.fields.when'))->dateTime(),
                    TextEntry::make('note')->label(__('admin.fields.note'))->placeholder('-'),
                ])->columns(4),
            ]),
        ]);
    }
}
