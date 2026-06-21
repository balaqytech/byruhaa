<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentPlans';

    protected static ?string $title = 'Payment plans';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                            $totalPercentage = collect((array) $value)
                                ->sum(fn (mixed $installment): int => is_array($installment) ? (int) ($installment['percentage'] ?? 0) : 0);

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('installments_count')
                    ->label(__('admin.fields.installments'))
                    ->counts('installments'),
                TextColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
