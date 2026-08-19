<?php

namespace App\Modules\Store\Filament\Resources\Orders\Schemas;

use App\Modules\Store\Models\Order;
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
            Section::make('Order')->columns(3)->schema([
                TextEntry::make('reference')->label('Reference')->copyable(),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('pickup_at')->label('Pickup')->dateTime()->placeholder('Immediate'),
                TextEntry::make('customer_name')->label('Customer'),
                TextEntry::make('customer_phone')->label('Phone'),
                TextEntry::make('customer_email')->label('Email')->placeholder('-'),
                TextEntry::make('recipient_name')->label('Recipient')->placeholder('-'),
                TextEntry::make('recipient_phone')->label('Recipient phone')->placeholder('-'),
                TextEntry::make('note')->label('Note')->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Totals')->columns(3)->schema([
                TextEntry::make('subtotal_baisa')->label('Subtotal')->state(fn (Order $record): string => MoneyFormatter::baisa($record->subtotal_baisa, 'OMR')),
                TextEntry::make('vat_baisa')->label('VAT')->state(fn (Order $record): string => MoneyFormatter::baisa($record->vat_baisa, 'OMR')),
                TextEntry::make('total_baisa')->label('Total')->state(fn (Order $record): string => MoneyFormatter::baisa($record->total_baisa, 'OMR')),
            ]),
            Section::make('Items')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('product_name')->label('Product'),
                    TextEntry::make('option_name')->label('Option'),
                    TextEntry::make('sku')->label('SKU'),
                    TextEntry::make('quantity')->label('Quantity'),
                    TextEntry::make('line_total_baisa')
                        ->label('Total')
                        ->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                    TextEntry::make('note')->label('Note')->placeholder('-'),
                ])->columns(3),
            ]),
            Section::make('Status history')->schema([
                RepeatableEntry::make('statusHistory')->schema([
                    TextEntry::make('from_status')->label('From')->placeholder('-'),
                    TextEntry::make('to_status')->label('To'),
                    TextEntry::make('created_at')->label('When')->dateTime(),
                    TextEntry::make('note')->label('Note')->placeholder('-'),
                ])->columns(4),
            ]),
        ]);
    }
}
