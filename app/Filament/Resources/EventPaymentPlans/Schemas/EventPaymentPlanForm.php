<?php

namespace App\Filament\Resources\EventPaymentPlans\Schemas;

use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventPaymentPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->label(__('admin.fields.event'))
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
                Repeater::make('installments')
                    ->label(__('admin.fields.installments'))
                    ->relationship('installments')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->maxLength(255),
                        TextInput::make('sequence')
                            ->label(__('admin.fields.sequence'))
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('percentage')
                            ->label(__('admin.fields.percentage'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%'),
                        DatePicker::make('due_date')
                            ->label(__('admin.fields.due_date'))
                            ->required(),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            $totalPercentage = 0;

                            foreach ((array) $value as $installment) {
                                if (is_array($installment)) {
                                    $totalPercentage += (int) ($installment['percentage'] ?? 0);
                                }
                            }

                            if ($totalPercentage !== 100) {
                                $fail(__('ui.messages.payment_plan_percentages_invalid'));
                            }
                        },
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->required(),
            ]);
    }
}
