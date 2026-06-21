<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('admin.fields.email_address'))
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone_number')
                    ->label(__('admin.fields.phone_number'))
                    ->tel()
                    ->required()
                    ->maxLength(255)
                    ->rules(['phone:OM'])
                    ->unique(ignoreRecord: true),
                TextInput::make('civil_id')
                    ->label(__('admin.fields.civil_id'))
                    ->maxLength(255),
                TextInput::make('address')
                    ->label(__('admin.fields.address'))
                    ->maxLength(255),
                TextInput::make('wilaya')
                    ->label(__('admin.fields.wilaya'))
                    ->maxLength(255),
                TextInput::make('area')
                    ->label(__('admin.fields.area'))
                    ->maxLength(255),
                KeyValue::make('additional_info')
                    ->label(__('admin.fields.additional_info'))
                    ->columnSpanFull(),
                DateTimePicker::make('email_verified_at')
                    ->label(__('admin.fields.email_verified_at')),
                TextInput::make('password')
                    ->label(__('admin.fields.password'))
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ]);
    }
}
