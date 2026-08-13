<?php

namespace App\Modules\Store\Filament\Pages;

use App\Modules\Store\Settings\StoreSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageStoreSettings extends SettingsPage
{
    protected static string $settings = StoreSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Store settings';

    protected static ?string $title = 'Store settings';

    protected static ?int $navigationSort = 91;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.store');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ordering')
                ->columns(2)
                ->schema([
                    Toggle::make('ordering_enabled')->label('Enable ordering'),
                    TextInput::make('vat_rate_percentage')->label('VAT percentage')->numeric()->minValue(0)->maxValue(100)->required(),
                    TextInput::make('reservation_duration_minutes')->label('Reservation duration (minutes)')->numeric()->minValue(1)->required(),
                    TextInput::make('opening_time')->label('Opening time')->placeholder('08:00'),
                    TextInput::make('closing_time')->label('Closing time')->placeholder('23:00'),
                ]),
            Section::make('Receipt and pickup')
                ->columns(2)
                ->schema([
                    TextInput::make('legal_name')->label('Legal name'),
                    TextInput::make('tax_number')->label('Tax number'),
                    TextInput::make('receipt_phone')->label('Receipt phone'),
                    TextInput::make('receipt_address')->label('Receipt address'),
                    Textarea::make('receipt_footer')->label('Receipt footer')->columnSpanFull(),
                    Textarea::make('pickup_instructions')->label('Pickup instructions')->columnSpanFull(),
                ]),
        ]);
    }
}
