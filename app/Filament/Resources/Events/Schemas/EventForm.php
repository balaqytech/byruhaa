<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('type')
                    ->required()
                    ->options([
                        'trip' => 'Trip',
                        'camp' => 'Camp',
                        'festival' => 'Festival',
                    ]),
                Select::make('status')
                    ->required()
                    ->options(EventStatus::options())
                    ->default(EventStatus::Draft->value),
                TextInput::make('seat_capacity')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('minimum_age')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(9),
                TextInput::make('maximum_age')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(16),
                TextInput::make('location')
                    ->maxLength(255),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Textarea::make('excerpt')
                    ->columnSpanFull()
                    ->maxLength(500),
                RichEditor::make('description_html')
                    ->label('Description')
                    ->columnSpanFull(),
                RichEditor::make('contract_terms_html')
                    ->label('Contract terms')
                    ->columnSpanFull(),
            ]);
    }
}
