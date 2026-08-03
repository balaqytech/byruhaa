<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Modules\Events\Models\Booking;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.event_relation_managers.bookings.heading'))
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->url(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])),
                TextColumn::make('customer.name')
                    ->label(__('admin.fields.customer'))
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

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.event_relation_managers.bookings.label');
    }
}
