<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Support\ContractVariables;
use App\Support\EventLandingPageRegistry;
use App\Support\Money\MoneyFactory;
use App\Support\ParticipantExtraFields;
use Brick\Money\Money;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make(__('admin.event_form.steps.basic_information'))
                        ->columns(2)
                        ->schema([
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
                                ->options(EventType::class),
                            Select::make('status')
                                ->label(__('admin.fields.status'))
                                ->required()
                                ->options(EventStatus::class)
                                ->default(EventStatus::Draft->value),
                            Select::make('enrollment_status')
                                ->label('حالة التسجيل')
                                ->required()
                                ->options(EventEnrollmentStatus::class)
                                ->default(EventEnrollmentStatus::BookingOpen->value),
                            Select::make('landing_page_key')
                                ->label(__('admin.fields.landing_page'))
                                ->options(fn (EventLandingPageRegistry $landingPages): array => $landingPages->options())
                                ->placeholder(__('admin.event_form.default_landing_page'))
                                ->helperText(__('admin.event_form.landing_page_help')),
                            TextInput::make('location')
                                ->label(__('admin.fields.location'))
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Textarea::make('excerpt')
                                ->label(__('admin.fields.excerpt'))
                                ->columnSpanFull()
                                ->maxLength(500),
                            RichEditor::make('description_html')
                                ->label(__('admin.fields.description'))
                                ->columnSpanFull(),
                        ]),
                    Step::make(__('admin.event_form.steps.capacity_pricing'))
                        ->columns(2)
                        ->schema([
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
                            DateTimePicker::make('starts_at')
                                ->label(__('admin.fields.starts_at')),
                            DateTimePicker::make('ends_at')
                                ->label(__('admin.fields.ends_at')),
                            Repeater::make('priceTiers')
                                ->relationship('priceTiers')
                                ->orderColumn('position')
                                ->label(__('admin.fields.price_tiers'))
                                ->helperText(__('admin.event_form.price_tiers_help'))
                                ->schema([
                                    TextInput::make('name')
                                        ->label(__('admin.fields.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('seat_capacity')
                                        ->label(__('admin.fields.tier_seat_capacity'))
                                        ->required()
                                        ->numeric()
                                        ->minValue(1),
                                    TextInput::make('price')
                                        ->label(__('admin.fields.tier_price'))
                                        ->required()
                                        ->rules([
                                            'regex:/^\d+(\.\d{1,3})?$/',
                                            fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                                $basePrice = $get('../../price');

                                                if (blank($basePrice) || blank($value)) {
                                                    return;
                                                }

                                                $basePriceBaisa = $basePrice instanceof Money
                                                    ? MoneyFactory::toMinor($basePrice)
                                                    : MoneyFactory::decimalStringToMinorUnits((string) $basePrice, 'OMR');
                                                $tierPriceBaisa = $value instanceof Money
                                                    ? MoneyFactory::toMinor($value)
                                                    : MoneyFactory::decimalStringToMinorUnits((string) $value, 'OMR');

                                                if ($tierPriceBaisa > $basePriceBaisa) {
                                                    $fail(__('ui.messages.price_tier_price_above_base'));
                                                }
                                            },
                                        ])
                                        ->formatStateUsing(fn (mixed $state): ?string => self::moneyInputState($state))
                                        ->suffix('OMR'),
                                    Toggle::make('is_active')
                                        ->label(__('admin.fields.is_active'))
                                        ->default(true),
                                ])
                                ->defaultItems(0)
                                ->columns(2)
                                ->collapsible()
                                ->reorderable()
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                ->addActionLabel(__('admin.actions.add_price_tier'))
                                ->columnSpanFull(),
                        ]),
                    Step::make(__('admin.event_form.steps.contract_terms'))
                        ->schema([
                            RichEditor::make('contract_terms_html')
                                ->label(__('admin.fields.contract_terms'))
                                ->mergeTags(ContractVariables::mergeTagLabels())
                                ->activePanel('mergeTags')
                                ->columnSpanFull(),
                        ]),
                    Step::make(__('admin.event_form.steps.participant_fields'))
                        ->schema([
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
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
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
