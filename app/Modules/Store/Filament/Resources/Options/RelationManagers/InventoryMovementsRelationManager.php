<?php

namespace App\Modules\Store\Filament\Resources\Options\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InventoryMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryMovements';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.store.inventory.history');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quantity_change')
                    ->label(__('admin.fields.quantity_change'))
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '+'.$state : (string) $state)
                    ->sortable(),
                TextColumn::make('stock_before')->label(__('admin.fields.before'))->sortable(),
                TextColumn::make('stock_after')->label(__('admin.fields.after'))->sortable(),
                TextColumn::make('reason')->label(__('admin.fields.reason'))->searchable(),
                TextColumn::make('created_at')->label(__('admin.fields.when'))->dateTime()->sortable(),
            ])
            ->recordActions([])
            ->headerActions([])
            ->toolbarActions([]);
    }
}
