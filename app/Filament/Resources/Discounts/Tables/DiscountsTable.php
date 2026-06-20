<?php

namespace App\Filament\Resources\Discounts\Tables;

use App\Models\Discount;
use App\Support\MoneyFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event.name')
                    ->label(__('admin.fields.event'))
                    ->placeholder(__('admin.fields.global')),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.discount_amount_per_member'))
                    ->formatStateUsing(fn (int $state, Discount $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('minimum_family_members')
                    ->label(__('admin.fields.minimum_family_members'))
                    ->placeholder('-'),
                TextColumn::make('maximum_family_members')
                    ->label(__('admin.fields.maximum_family_members'))
                    ->placeholder('-'),
                TextColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                    ->badge(),
                TextColumn::make('starts_at')
                    ->label(__('admin.fields.starts_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('admin.fields.ends_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
