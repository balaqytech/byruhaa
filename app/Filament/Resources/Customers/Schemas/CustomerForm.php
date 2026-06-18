<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                    ->rules(['required_without:phone_number'])
                    ->unique(ignoreRecord: true),
                TextInput::make('phone_number')
                    ->label(__('admin.fields.phone_number'))
                    ->tel()
                    ->maxLength(255)
                    ->rules(['required_without:email', 'phone:OM'])
                    ->unique(ignoreRecord: true),
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
