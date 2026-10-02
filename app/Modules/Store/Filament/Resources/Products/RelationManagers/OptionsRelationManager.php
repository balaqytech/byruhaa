<?php

namespace App\Modules\Store\Filament\Resources\Products\RelationManagers;

use App\Modules\Store\Actions\AdjustStock;
use App\Modules\Store\Models\ProductOption;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.store.inventory.options_overview');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description(__('admin.store.inventory.options_overview_help'))
            ->columns([
                TextColumn::make('name')->label(__('admin.fields.name'))->searchable(),
                TextColumn::make('sku')->label(__('admin.fields.sku'))->searchable(),
                TextColumn::make('price_baisa')
                    ->label(__('admin.fields.price'))
                    ->formatStateUsing(fn (int $state, ProductOption $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('member_price_baisa')
                    ->label(__('admin.fields.member_price'))
                    ->formatStateUsing(fn (?int $state, ProductOption $record): string => $state === null ? '—' : MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('is_default')
                    ->label(__('admin.fields.is_default'))
                    ->badge()
                    ->formatStateUsing(fn (?bool $state): string => $state ? __('admin.statuses.default') : __('admin.statuses.no')),
                TextColumn::make('is_available')
                    ->label(__('admin.fields.is_available'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive')),
                TextColumn::make('stock_on_hand')
                    ->label(__('admin.fields.stock'))
                    ->state(fn (ProductOption $record): string => $record->tracks_inventory ? (string) $record->stock_on_hand : '—'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                Action::make('adjust-stock')
                    ->label(__('admin.store.actions.adjust_stock'))
                    ->visible(fn (ProductOption $record): bool => $record->tracks_inventory)
                    ->schema([
                        TextInput::make('quantity_change')
                            ->label(__('admin.fields.quantity_change'))
                            ->numeric()
                            ->integer()
                            ->required(),
                        TextInput::make('reason')
                            ->label(__('admin.fields.reason'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (ProductOption $record, array $data, AdjustStock $adjustStock): void {
                        $actorId = auth()->id();
                        $adjustStock->execute(
                            $record,
                            (int) $data['quantity_change'],
                            (string) $data['reason'],
                            is_numeric($actorId) ? (int) $actorId : null,
                        );
                    }),
            ]);
    }
}
