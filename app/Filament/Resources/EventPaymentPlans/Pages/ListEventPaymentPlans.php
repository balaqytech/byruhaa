<?php

namespace App\Filament\Resources\EventPaymentPlans\Pages;

use App\Filament\Resources\EventPaymentPlans\EventPaymentPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventPaymentPlans extends ListRecords
{
    protected static string $resource = EventPaymentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
