<?php

namespace App\Modules\Store\Filament\Resources\Products\Tables;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Product;
use App\Support\MoneyFormatter;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['defaultOption', 'featuredImage.media'])->withCount('options'))
            ->columns([
                ImageColumn::make('featured_image_preview')
                    ->label(__('admin.fields.image'))
                    ->state(fn (Product $record): ?string => $record->featuredImage?->getUrl())
                    ->square(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('admin.fields.category'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('starting_price')
                    ->label(__('admin.fields.price'))
                    ->state(fn (Product $record): string => $record->defaultOption
                        ? MoneyFormatter::baisa($record->defaultOption->price_baisa, $record->defaultOption->currency)
                        : '—'),
                TextColumn::make('options_count')
                    ->label(__('admin.store.product_list_tabs.options_count'))
                    ->sortable(),
                TextColumn::make('is_featured')
                    ->label(__('admin.fields.is_featured'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(ProductStatus::class),
                SelectFilter::make('category_id')
                    ->label(__('admin.fields.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
