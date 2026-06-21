<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Bookings';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->url(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])),
                TextColumn::make('event.name')
                    ->label(__('admin.fields.event'))
                    ->searchable(),
                TextColumn::make('state')
                    ->label(__('admin.fields.state'))
                    ->badge(),
                TextColumn::make('total_baisa')
                    ->label(__('admin.fields.total'))
                    ->formatStateUsing(fn (int $state, Booking $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
