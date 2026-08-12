<?php

namespace App\Modules\Store\Filament\Resources\Options\Tables;

use App\Modules\Store\Models\ProductOption;
use App\Support\MoneyFormatter;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductOptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('admin.fields.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label(__('admin.fields.sku'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price_baisa')
                    ->label(__('admin.fields.price'))
                    ->formatStateUsing(fn (int $state, ProductOption $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('is_available')
                    ->label(__('admin.fields.is_available'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('is_default')
                    ->label(__('admin.fields.is_default'))
                    ->formatStateUsing(fn (?bool $state): string => $state === true ? __('admin.statuses.default') : __('admin.statuses.no'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label(__('admin.fields.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_available')
                    ->label(__('admin.fields.is_available')),
                TernaryFilter::make('is_default')
                    ->label(__('admin.fields.is_default')),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
