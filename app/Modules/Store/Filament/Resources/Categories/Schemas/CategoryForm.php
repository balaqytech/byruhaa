<?php

namespace App\Modules\Store\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Slimani\MediaManager\Form\MediaPicker;

class CategoryForm
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
                TextInput::make('short_name')
                    ->label(__('admin.fields.short_name'))
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('admin.fields.description'))
                    ->maxLength(500)
                    ->columnSpanFull(),
                MediaPicker::make('image_id')
                    ->label(__('admin.store.category_media.image'))
                    ->helperText(__('admin.store.category_media.image_help'))
                    ->relationship('image')
                    ->directory('store/categories')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->image()
                    ->maxSize(5120)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
            ]);
    }
}
