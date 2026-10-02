<?php

namespace App\Modules\Store\Filament\Resources\Products\RelationManagers;

use App\Modules\Store\Models\Product;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InventoryMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryMovements';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.store.inventory.history');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                TextColumn::make('productOption.name')
                    ->label(__('admin.fields.option'))
                    ->searchable(),
                TextColumn::make('quantity_change')
                    ->label(__('admin.fields.quantity_change'))
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '+'.$state : (string) $state),
                TextColumn::make('stock_before')->label(__('admin.fields.before')),
                TextColumn::make('stock_after')->label(__('admin.fields.after')),
                TextColumn::make('reason')->label(__('admin.fields.reason'))->searchable(),
                TextColumn::make('created_at')->label(__('admin.fields.when'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_option_id')
                    ->label(__('admin.fields.option'))
                    ->options(fn (): array => $this->getOwnerRecord() instanceof Product
                        ? $this->getOwnerRecord()->options()->pluck('name', 'id')->all()
                        : []),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
