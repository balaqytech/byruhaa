<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->label(__('admin.fields.discount_amount_per_member'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('baisa'),
                DateTimePicker::make('starts_at')
                    ->label(__('admin.fields.starts_at')),
                DateTimePicker::make('ends_at')
                    ->label(__('admin.fields.ends_at'))
                    ->rules([
                        'nullable',
                        'date',
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $startsAt = $get('starts_at');

                            if (blank($value) || blank($startsAt)) {
                                return;
                            }

                            $startsAtTimestamp = strtotime((string) $startsAt);
                            $endsAtTimestamp = strtotime((string) $value);

                            if ($startsAtTimestamp === false || $endsAtTimestamp === false) {
                                return;
                            }

                            if ($endsAtTimestamp < $startsAtTimestamp) {
                                $fail(__('validation.after_or_equal', [
                                    'attribute' => __('admin.fields.ends_at'),
                                    'date' => __('admin.fields.starts_at'),
                                ]));
                            }
                        },
                    ]),
                TextInput::make('minimum_family_members')
                    ->label(__('admin.fields.minimum_family_members'))
                    ->numeric()
                    ->minValue(1),
                TextInput::make('maximum_family_members')
                    ->label(__('admin.fields.maximum_family_members'))
                    ->numeric()
                    ->minValue(1)
                    ->rules([
                        'nullable',
                        'integer',
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $minimumFamilyMembers = $get('minimum_family_members');

                            if (blank($value) || blank($minimumFamilyMembers)) {
                                return;
                            }

                            if ((int) $value < (int) $minimumFamilyMembers) {
                                $fail(__('validation.gte.numeric', [
                                    'attribute' => __('admin.fields.maximum_family_members'),
                                    'value' => __('admin.fields.minimum_family_members'),
                                ]));
                            }
                        },
                    ]),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
            ]);
    }
}
