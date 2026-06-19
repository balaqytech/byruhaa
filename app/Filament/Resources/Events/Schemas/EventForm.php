<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Support\ContractVariables;
use App\Support\ParticipantExtraFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
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
                Repeater::make('participant_extra_fields')
                    ->label('Participant extra fields')
                    ->schema([
                        TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->rules(['regex:/^[A-Za-z][A-Za-z0-9_]*$/'])
                            ->maxLength(80),
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Type')
                            ->options(ParticipantExtraFields::typeOptions())
                            ->default('text')
                            ->live()
                            ->required(),
                        Toggle::make('required')
                            ->label('Required')
                            ->default(false),
                        Textarea::make('options')
                            ->label('Options')
                            ->helperText('One option per line. Used only for select and radio fields.')
                            ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'radio'], true))
                            ->required(fn (Get $get): bool => in_array($get('type'), ['select', 'radio'], true))
                            ->columnSpanFull(),
                        TextInput::make('placeholder')
                            ->label('Placeholder')
                            ->maxLength(255),
                        Textarea::make('help_text')
                            ->label('Help text')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(0)
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['key'] ?? null)
                    ->columnSpanFull(),
                Section::make('Contract variables')
                    ->description('Copy these tokens into the contract terms. They are resolved when a booking is approved.')
                    ->schema([
                        Html::make(self::contractVariableReferenceHtml()),
                    ])
                    ->compact()
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    private static function contractVariableReferenceHtml(): HtmlString
    {
        $html = '<div class="space-y-4 text-sm">';

        foreach (ContractVariables::definitions() as $group => $variables) {
            $html .= '<div>';
            $html .= '<div class="mb-2 font-medium text-gray-950 dark:text-white">'.e($group).'</div>';
            $html .= '<div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">';

            foreach ($variables as $key => $label) {
                $token = '{{ '.$key.' }}';

                $html .= '<div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-white/10 dark:bg-white/5">';
                $html .= '<code dir="ltr" class="block select-all text-xs font-semibold text-primary-700 dark:text-primary-300">'.e($token).'</code>';
                $html .= '<div class="mt-1 text-xs text-gray-600 dark:text-gray-400">'.e($label).'</div>';
                $html .= '</div>';
            }

            $html .= '</div></div>';
        }

        return new HtmlString($html.'</div>');
    }
}
