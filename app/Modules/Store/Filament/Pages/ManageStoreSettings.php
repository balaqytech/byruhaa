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

    protected static ?int $navigationSort = 91;

    public static function getNavigationLabel(): string
    {
        return __('admin.store.settings.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.store.settings.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.store');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.store.sections.ordering'))
                ->columns(2)
                ->schema([
                    Toggle::make('ordering_enabled')->label(__('admin.store.settings.enable_ordering')),
                    TextInput::make('vat_rate_percentage')->label(__('admin.store.settings.vat_rate_percentage'))->numeric()->minValue(0)->maxValue(100)->required(),
                    TextInput::make('reservation_duration_minutes')->label(__('admin.store.settings.reservation_duration_minutes'))->numeric()->minValue(1)->required(),
                    TextInput::make('opening_time')->label(__('admin.store.settings.opening_time'))->placeholder('08:00'),
                    TextInput::make('closing_time')->label(__('admin.store.settings.closing_time'))->placeholder('23:00'),
                ]),
            Section::make(__('admin.store.sections.receipt_pickup'))
                ->columns(2)
                ->schema([
                    TextInput::make('legal_name')->label(__('admin.store.settings.legal_name')),
                    TextInput::make('tax_number')->label(__('admin.store.settings.tax_number')),
                    TextInput::make('receipt_phone')->label(__('admin.store.settings.receipt_phone')),
                    TextInput::make('receipt_address')->label(__('admin.store.settings.receipt_address')),
                    Textarea::make('receipt_footer')->label(__('admin.store.settings.receipt_footer'))->columnSpanFull(),
                    Textarea::make('pickup_instructions')->label(__('admin.store.settings.pickup_instructions'))->columnSpanFull(),
                ]),
        ]);
    }
}
