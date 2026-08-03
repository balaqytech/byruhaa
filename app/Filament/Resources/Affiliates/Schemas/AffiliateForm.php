<?php

namespace App\Filament\Resources\Affiliates\Schemas;

use App\Enums\AffiliateStatus;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class AffiliateForm
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
                    ->dehydrateStateUsing(fn (?string $state): ?string => app(PhoneNumberNormalizer::class)->normalize($state))
                    ->unique(ignoreRecord: true),
                TextInput::make('code')
                    ->label(__('admin.fields.code'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(AffiliateStatus::class)
                    ->required()
                    ->default(AffiliateStatus::Pending->value),
                DateTimePicker::make('reviewed_at')
                    ->label(__('admin.fields.reviewed_at')),
                Textarea::make('review_notes')
                    ->label(__('admin.fields.review_notes'))
                    ->columnSpanFull(),
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
