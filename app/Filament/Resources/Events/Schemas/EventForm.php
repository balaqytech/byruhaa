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
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('type')
                    ->label(__('admin.fields.type'))
                    ->required()
                    ->options([
                        'trip' => __('admin.event_types.trip'),
                        'camp' => __('admin.event_types.camp'),
                        'festival' => __('admin.event_types.festival'),
                    ]),
                Select::make('status')
                    ->label(__('admin.fields.status'))
                    ->required()
                    ->options(EventStatus::options())
                    ->default(EventStatus::Draft->value),
                TextInput::make('seat_capacity')
                    ->label(__('admin.fields.seat_capacity'))
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('price_baisa')
                    ->label(__('admin.fields.price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->suffix('baisa'),
                TextInput::make('currency')
                    ->label(__('admin.fields.currency'))
                    ->required()
                    ->default('OMR')
                    ->maxLength(3),
                TextInput::make('minimum_age')
                    ->label(__('admin.fields.minimum_age'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(9),
                TextInput::make('maximum_age')
                    ->label(__('admin.fields.maximum_age'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(16),
                TextInput::make('location')
                    ->label(__('admin.fields.location'))
                    ->maxLength(255),
                DateTimePicker::make('starts_at')
                    ->label(__('admin.fields.starts_at')),
                DateTimePicker::make('ends_at')
                    ->label(__('admin.fields.ends_at')),
                Textarea::make('excerpt')
                    ->label(__('admin.fields.excerpt'))
                    ->columnSpanFull()
                    ->maxLength(500),
                RichEditor::make('description_html')
                    ->label(__('admin.fields.description'))
                    ->columnSpanFull(),
                RichEditor::make('contract_terms_html')
                    ->label(__('admin.fields.contract_terms'))
                    ->columnSpanFull(),
            ]);
    }
}
