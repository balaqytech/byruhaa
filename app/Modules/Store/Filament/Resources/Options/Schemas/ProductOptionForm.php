<?php

namespace App\Modules\Store\Filament\Resources\Options\Schemas;

use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Slimani\MediaManager\Form\MediaPicker;

class ProductOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.store.sections.identity'))
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label(__('admin.fields.product'))
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sku')
                            ->label(__('admin.fields.sku'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label(__('admin.fields.price'))
                            ->helperText(__('admin.store.price_includes_vat_help'))
                            ->required()
                            ->rules(['regex:/^\d+(\.\d{1,3})?$/'])
                            ->formatStateUsing(fn (mixed $state): ?string => self::moneyInputState($state))
                            ->suffix('OMR'),
                        TextInput::make('currency')
                            ->label(__('admin.fields.currency'))
                            ->default('OMR')
                            ->required()
                            ->maxLength(3)
                            ->minLength(3),
                        TextInput::make('sort_order')
                            ->label(__('admin.fields.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),
                Section::make(__('admin.store.sections.media'))
                    ->columns(2)
                    ->schema([
                        MediaPicker::make('image_id')
                            ->label(__('admin.fields.image'))
                            ->relationship('image')
                            ->acceptedFileTypes(['image/*'])
                            ->image(),
                        Toggle::make('is_available')
                            ->label(__('admin.fields.is_available'))
                            ->default(true),
                        Toggle::make('tracks_inventory')
                            ->label(__('admin.fields.tracks_inventory'))
                            ->default(false),
                        TextInput::make('stock_on_hand')
                            ->label(__('admin.fields.stock_on_hand'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
                        Toggle::make('is_default')
                            ->label(__('admin.fields.is_default'))
                            ->helperText(__('admin.store.default_option_help'))
                            ->default(false),
                    ]),
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
