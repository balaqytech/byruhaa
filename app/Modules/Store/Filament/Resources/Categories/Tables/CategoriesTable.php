<?php

namespace App\Modules\Store\Filament\Resources\Categories\Tables;

use App\Modules\Store\Models\Category;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('image.media'))
            ->columns([
                ImageColumn::make('image_preview')
                    ->label(__('admin.fields.image'))
                    ->state(fn (Category $record): ?string => $record->image?->getUrl())
                    ->square(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
