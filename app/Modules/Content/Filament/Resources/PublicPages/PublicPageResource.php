<?php

namespace App\Modules\Content\Filament\Resources\PublicPages;

use App\Modules\Content\Filament\Resources\PublicPages\Pages\EditPublicPage;
use App\Modules\Content\Filament\Resources\PublicPages\Pages\ListPublicPages;
use App\Modules\Content\Filament\Resources\PublicPages\Schemas\PublicPageForm;
use App\Modules\Content\Filament\Resources\PublicPages\Tables\PublicPagesTable;
use App\Modules\Content\Models\PublicPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PublicPageResource extends Resource
{
    protected static ?string $model = PublicPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $slug = 'public-pages';

    public static function form(Schema $schema): Schema
    {
        return PublicPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PublicPagesTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.public_pages.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.public_pages.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.public_pages.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.content');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicPages::route('/'),
            'edit' => EditPublicPage::route('/{record}/edit'),
        ];
    }
}
