<?php

namespace App\Filament\Resources\EventPaymentPlans\Pages;

use App\Filament\Resources\EventPaymentPlans\EventPaymentPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventPaymentPlan extends EditRecord
{
    protected static string $resource = EventPaymentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
