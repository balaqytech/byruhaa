<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Models\Discount;
use App\Support\Money\MoneyFactory;
use App\Support\MoneyFormatter;
use Brick\Money\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('amount')
                    ->label(__('admin.fields.discount_amount_per_member'))
                    ->required()
                    ->rules(['regex:/^\d+(\.\d{1,3})?$/'])
                    ->formatStateUsing(fn (mixed $state): ?string => self::moneyInputState($state))
                    ->suffix('OMR'),
                DateTimePicker::make('starts_at')
                    ->label(__('admin.fields.starts_at')),
                DateTimePicker::make('ends_at')
                    ->label(__('admin.fields.ends_at')),
                TextInput::make('minimum_family_members')
                    ->label(__('admin.fields.minimum_family_members'))
                    ->numeric()
                    ->minValue(1),
                TextInput::make('maximum_family_members')
                    ->label(__('admin.fields.maximum_family_members'))
                    ->numeric()
                    ->minValue(1),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.event_relation_managers.discounts.heading'))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.discount_amount_per_member'))
                    ->formatStateUsing(fn (int $state, Discount $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('minimum_family_members')
                    ->label(__('admin.fields.minimum_family_members'))
                    ->placeholder('-'),
                TextColumn::make('maximum_family_members')
                    ->label(__('admin.fields.maximum_family_members'))
                    ->placeholder('-'),
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

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.event_relation_managers.discounts.label');
    }

    private static function moneyInputState(mixed $state): ?string
    {
        if ($state instanceof Money) {
            return MoneyFactory::formatMoneyAmount($state);
        }

        return blank($state) ? null : (string) $state;
    }
}
