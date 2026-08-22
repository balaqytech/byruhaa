<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.user_form.sections.account'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.fields.email_address'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label(__('admin.fields.password'))
                            ->password()
                            ->revealable()
                            ->rule(Password::defaults())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        DateTimePicker::make('email_verified_at')
                            ->label(__('admin.fields.email_verified_at')),
                        Select::make('role')
                            ->label(__('admin.fields.account_role'))
                            ->options(UserRole::options())
                            ->required(),
                    ]),
                Section::make(__('admin.user_form.sections.permissions'))
                    ->schema([
                        Select::make('roles')
                            ->label(__('admin.fields.permission_roles'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(__('admin.user_form.help.permission_roles')),
                    ]),
            ]);
    }
}
