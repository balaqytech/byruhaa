<?php

namespace App\Modules\Store\Filament\Resources\Products\Schemas;

use App\Modules\Store\Enums\ProductStatus;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Slimani\MediaManager\Form\MediaPicker;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.store.sections.identity'))
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
                        Select::make('category_id')
                            ->label(__('admin.fields.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label(__('admin.fields.status'))
                            ->options(ProductStatus::class)
                            ->default(ProductStatus::Draft->value)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label(__('admin.fields.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),
                Section::make(__('admin.store.sections.content'))
                    ->schema([
                        Textarea::make('description')
                            ->label(__('admin.fields.description'))
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.store.sections.media'))
                    ->schema([
                        MediaPicker::make('featured_image_id')
                            ->label(__('admin.fields.featured_image'))
                            ->relationship('featuredImage')
                            ->acceptedFileTypes(['image/*'])
                            ->image(),
                    ]),
                Section::make(__('admin.resources.store_product_options.plural_label'))
                    ->schema([
                        Repeater::make('options')
                            ->relationship('options')
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sku')
                                    ->label(__('admin.fields.sku'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('price')
                                    ->label(__('admin.fields.price'))
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
                                    ->dehydrated(false)
                                    ->visible(fn (callable $get): bool => (bool) $get('tracks_inventory')),
                                Toggle::make('is_default')
                                    ->label(__('admin.fields.is_default'))
                                    ->default(true),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->required()
                            ->columns(3),
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
