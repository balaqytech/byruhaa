<?php

namespace App\Filament\Resources\PublicPages\Schemas;

use App\Modules\Content\Enums\PublicPageStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Slimani\MediaManager\Form\RichEditor\MediaManagerRichContentPlugin;

class PublicPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.public_page_form.sections.identity'))
                ->columns(2)
                ->schema([
                    TextInput::make('key')
                        ->label(__('admin.fields.key'))
                        ->disabled()
                        ->dehydrated(),
                    TextInput::make('title')
                        ->label(__('admin.fields.title'))
                        ->required()
                        ->maxLength(255),
                    Select::make('status')
                        ->label(__('admin.fields.status'))
                        ->options(PublicPageStatus::class)
                        ->required(),
                    DateTimePicker::make('published_at')
                        ->label(__('admin.fields.published_at')),
                    DateTimePicker::make('effective_at')
                        ->label(__('admin.fields.effective_at')),
                    TextInput::make('version')
                        ->label(__('admin.fields.version'))
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                ]),
            Section::make(__('admin.public_page_form.sections.content'))
                ->schema([
                    RichEditor::make('content')
                        ->label(__('admin.fields.content'))
                        ->plugins([
                            MediaManagerRichContentPlugin::make()
                                ->acceptedFileTypes(['image/*']),
                        ])
                        ->required()
                        ->columnSpanFull(),
                ]),
            Section::make(__('admin.public_page_form.sections.seo'))
                ->columns(2)
                ->schema([
                    TextInput::make('meta_title')
                        ->label(__('admin.fields.meta_title'))
                        ->maxLength(255),
                    Textarea::make('meta_description')
                        ->label(__('admin.fields.meta_description'))
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
