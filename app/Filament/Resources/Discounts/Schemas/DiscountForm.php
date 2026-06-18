<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('event_id')
                    ->label(__('admin.fields.event'))
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('amount_baisa')
                    ->label(__('admin.fields.discount_amount'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('baisa'),
                DateTimePicker::make('starts_at')
                    ->label(__('admin.fields.starts_at')),
                DateTimePicker::make('ends_at')
                    ->label(__('admin.fields.ends_at')),
                TextInput::make('minimum_family_members')
                    ->label(__('admin.fields.minimum_family_members'))
                    ->numeric()
                    ->minValue(1),
                TextInput::make('maximum_family_members')
                    ->label(__('admin.fields.maximum_family_members'))
                    ->numeric()
                    ->minValue(1),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
            ]);
    }
}
