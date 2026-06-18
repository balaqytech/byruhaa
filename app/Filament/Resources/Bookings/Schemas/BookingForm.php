<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\States\Booking\BookingState;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->disabled(),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->disabled(),
                Select::make('event_id')
                    ->relationship('event', 'name')
                    ->disabled(),
                TextInput::make('state')
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof BookingState ? $state->label() : (string) $state)
                    ->disabled(),
                Textarea::make('review_notes')
                    ->columnSpanFull(),
            ]);
    }
}
