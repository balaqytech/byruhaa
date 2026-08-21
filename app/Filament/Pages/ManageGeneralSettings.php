<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
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
            Section::make('هوية الموقع العامة')
                ->columns(2)
                ->schema([
                    TextInput::make('public_name')
                        ->label('الاسم الظاهر للزوار')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('commercial_registration_number')
                        ->label('رقم السجل التجاري')
                        ->maxLength(50),
                    TextInput::make('tax_number')
                        ->label('الرقم الضريبي')
                        ->helperText('اتركه فارغًا إلى حين اعتماده.')
                        ->maxLength(50),
                ]),
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
