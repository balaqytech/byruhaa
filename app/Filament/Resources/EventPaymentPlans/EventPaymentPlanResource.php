<?php

namespace App\Filament\Resources\EventPaymentPlans;

use App\Filament\Resources\EventPaymentPlans\Pages\CreateEventPaymentPlan;
use App\Filament\Resources\EventPaymentPlans\Pages\EditEventPaymentPlan;
use App\Filament\Resources\EventPaymentPlans\Pages\ListEventPaymentPlans;
use App\Filament\Resources\EventPaymentPlans\Schemas\EventPaymentPlanForm;
use App\Filament\Resources\EventPaymentPlans\Tables\EventPaymentPlansTable;
use App\Models\EventPaymentPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventPaymentPlanResource extends Resource
{
    protected static ?string $model = EventPaymentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return EventPaymentPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventPaymentPlansTable::configure($table);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.event_payment_plans.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.event_payment_plans.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.event_payment_plans.navigation_label');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventPaymentPlans::route('/'),
            'create' => CreateEventPaymentPlan::route('/create'),
            'edit' => EditEventPaymentPlan::route('/{record}/edit'),
        ];
    }
}
