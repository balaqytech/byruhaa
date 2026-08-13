<?php

namespace App\Modules\Store\Filament\Resources\Options\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryMovements';

    protected static ?string $title = 'Inventory history';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quantity_change')
                    ->label('Change')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '+'.$state : (string) $state)
                    ->sortable(),
                TextColumn::make('stock_before')->label('Before')->sortable(),
                TextColumn::make('stock_after')->label('After')->sortable(),
                TextColumn::make('reason')->label('Reason')->searchable(),
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
            ])
            ->recordActions([])
            ->headerActions([])
            ->toolbarActions([]);
    }
}
