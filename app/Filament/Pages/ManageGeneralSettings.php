<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageGeneralSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'الإعدادات العامة';

    protected static ?string $title = 'الإعدادات العامة';

    protected static ?int $navigationSort = 90;

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'الإعدادات';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('حالة الموقع')
                ->description('يمكنك إخفاء الموقع العام مؤقتًا مع إبقاء لوحة الإدارة متاحة.')
                ->schema([
                    Toggle::make('coming_soon_enabled')
                        ->label('تفعيل صفحة «قريبًا»')
                        ->helperText('عند التفعيل سيشاهد الزوار صفحة «قريبًا»، بينما تظل لوحة الإدارة وواجهات التكامل متاحة.')
                        ->onColor('warning'),
                ]),
        ]);
    }
}
