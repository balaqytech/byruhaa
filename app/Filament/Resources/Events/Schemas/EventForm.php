<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Support\ContractVariables;
use App\Support\Money\MoneyFactory;
use App\Support\ParticipantExtraFields;
use Brick\Money\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
                TextInput::make('price')
                    ->label(__('admin.fields.price'))
                    ->required()
                    ->rules(['regex:/^\d+(\.\d{1,3})?$/'])
                    ->default('0.000')
                    ->formatStateUsing(fn (mixed $state): ?string => self::moneyInputState($state))
                    ->suffix('OMR'),
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
                    ->mergeTags(ContractVariables::mergeTagLabels())
                    ->activePanel('mergeTags')
                    ->columnSpanFull(),
                Repeater::make('participant_extra_fields')
                    ->label(__('admin.participant_extra_fields.heading'))
                    ->schema([
                        TextInput::make('key')
                            ->label(__('admin.participant_extra_fields.key'))
                            ->required()
                            ->rules(['regex:/^[A-Za-z][A-Za-z0-9_]*$/'])
                            ->maxLength(80),
                        TextInput::make('label')
                            ->label(__('admin.participant_extra_fields.label'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('admin.participant_extra_fields.type'))
                            ->options(ParticipantExtraFields::typeOptions())
                            ->default('text')
                            ->live()
                            ->required(),
                        Toggle::make('required')
                            ->label(__('admin.participant_extra_fields.required'))
                            ->default(false),
                        Textarea::make('options')
                            ->label(__('admin.participant_extra_fields.options'))
                            ->helperText(__('admin.participant_extra_fields.options_help'))
                            ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'radio'], true))
                            ->required(fn (Get $get): bool => in_array($get('type'), ['select', 'radio'], true))
                            ->columnSpanFull(),
                        TextInput::make('placeholder')
                            ->label(__('admin.participant_extra_fields.placeholder'))
                            ->maxLength(255),
                        Textarea::make('help_text')
                            ->label(__('admin.participant_extra_fields.help_text'))
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['key'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    private static function moneyInputState(mixed $state): ?string
    {
        if ($state instanceof Money) {
            return MoneyFactory::formatMoneyAmount($state);
        }

        return blank($state) ? null : (string) $state;
    }
}
