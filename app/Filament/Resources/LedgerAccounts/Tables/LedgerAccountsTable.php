<?php

namespace App\Filament\Resources\LedgerAccounts\Tables;

use App\Enums\LedgerAccountType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LedgerAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount('entries')
                ->orderBy('code'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin.fields.code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.fields.type'))
                    ->formatStateUsing(fn (LedgerAccountType $state): string => $state->label())
                    ->badge()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label(__('admin.fields.currency')),
                TextColumn::make('entries_count')
                    ->label(__('admin.fields.entries'))
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.fields.type'))
                    ->options(self::accountTypeOptions()),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function accountTypeOptions(): array
    {
        return collect(LedgerAccountType::cases())
            ->mapWithKeys(fn (LedgerAccountType $type): array => [$type->value => $type->label()])
            ->all();
    }
}
