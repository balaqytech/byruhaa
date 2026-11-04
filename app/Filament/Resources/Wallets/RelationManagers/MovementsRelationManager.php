<?php

namespace App\Filament\Resources\Wallets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin_wallets.movements');
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('type')->label(__('admin_wallets.type'))->badge()->formatStateUsing(fn (string $state): string => __('admin_wallets.types.'.$state)),
            TextColumn::make('order_reference')->label(__('admin_wallets.order'))->searchable()->placeholder('-'),
            TextColumn::make('topUp.reference')->label(__('admin_wallets.top_up'))->searchable()->placeholder('-'),
            TextColumn::make('credit_baisa')->label(__('admin.fields.credit'))->money('OMR', divideBy: 1000),
            TextColumn::make('debit_baisa')->label(__('admin.fields.debit'))->money('OMR', divideBy: 1000),
            TextColumn::make('balance_after_baisa')->label(__('admin_wallets.balance_after'))->money('OMR', divideBy: 1000),
            TextColumn::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->sortable(),
        ])->filters([SelectFilter::make('type')->label(__('admin_wallets.type'))->options(__('admin_wallets.types'))])
            ->headerActions([])->recordActions([])->toolbarActions([]);
    }
}
