<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\BlogPostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Slimani\MediaManager\Form\MediaPicker;
use Slimani\MediaManager\Form\RichEditor\MediaManagerRichContentPlugin;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('admin.fields.title'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('blog_post_category_id')
                    ->label(__('admin.fields.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(BlogPostStatus::class)
                    ->default(BlogPostStatus::Draft->value)
                    ->required(),
                DateTimePicker::make('published_at')
                    ->label(__('admin.fields.published_at')),
                MediaPicker::make('featured_image_id')
                    ->label(__('admin.fields.featured_image'))
                    ->relationship('featuredImage')
                    ->acceptedFileTypes(['image/*'])
                    ->image()
                    ->columnSpanFull(),
                Textarea::make('excerpt')
                    ->label(__('admin.fields.excerpt'))
                    ->maxLength(500)
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->label(__('admin.fields.content'))
                    ->plugins([
                        MediaManagerRichContentPlugin::make()
                            ->acceptedFileTypes(['image/*']),
                    ])
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('meta_title')
                    ->label(__('admin.fields.meta_title'))
                    ->maxLength(255),
                Textarea::make('meta_description')
                    ->label(__('admin.fields.meta_description'))
                    ->columnSpanFull(),
            ]);
    }
}
