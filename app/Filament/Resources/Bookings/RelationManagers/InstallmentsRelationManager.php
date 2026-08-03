<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Modules\Events\Models\BookingInstallment;
use App\Support\MoneyFormatter;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Installments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('sequence')
                    ->label(__('admin.fields.sequence'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->placeholder('-'),
                TextColumn::make('percentage')
                    ->label(__('admin.fields.percentage'))
                    ->suffix('%'),
                TextColumn::make('due_date')
                    ->label(__('admin.fields.due_date'))
                    ->date(),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->formatStateUsing(fn (int $state, BookingInstallment $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('state')
                    ->label(__('admin.fields.state'))
                    ->badge(),
                TextColumn::make('paid_at')
                    ->label(__('admin.fields.paid_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
