<?php

namespace App\Modules\Store\Filament\Resources\Orders\Schemas;

use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\OrderState;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                TextEntry::make('recipient_name')->label(__('admin.fields.recipient')),
                TextEntry::make('recipient_phone')->label(__('admin.fields.recipient_phone')),
                TextEntry::make('note')->label(__('admin.fields.note'))->placeholder('-')->columnSpanFull(),
            ]),
            Section::make(__('admin.store.sections.totals'))->columns(3)->schema([
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
                    TextEntry::make('line_total_baisa')
                        ->label(__('admin.fields.total'))
                        ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                    TextEntry::make('note')->label(__('admin.fields.note'))->placeholder('-'),
                ])->columns(3),
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
