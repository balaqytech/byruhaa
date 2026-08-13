<?php

namespace App\Modules\Store\Filament\Resources\Orders\Tables;

use App\Modules\Store\Enums\OrderStatus;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest('id'))
            ->columns([
                TextColumn::make('reference')->label('Reference')->searchable()->sortable()->copyable(),
                TextColumn::make('customer_name')->label('Customer')->searchable(),
                TextColumn::make('customer_phone')->label('Phone')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('pickup_at')->label('Pickup')->dateTime()->sortable()->placeholder('Immediate'),
                TextColumn::make('total_baisa')->label('Total')->money('OMR', divideBy: 1000)->sortable(),
                TextColumn::make('created_at')->label('Created')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(fn (): array => collect(OrderStatus::cases())->mapWithKeys(fn ($status): array => [$status->value => str($status->value)->headline()])->all()),
                Filter::make('pickup_date')->form([
                    DatePicker::make('date'),
                ])->query(fn (Builder $query, array $data): Builder => filled($data['date'] ?? null) ? $query->whereDate('pickup_at', $data['date']) : $query),
            ])
            ->recordActions([ViewAction::make()])
            ->toolbarActions([]);
    }
}
