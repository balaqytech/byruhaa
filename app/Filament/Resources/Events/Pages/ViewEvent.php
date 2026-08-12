<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\Actions\CancelEventAction;
use App\Filament\Resources\Events\EventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.resources.events.label');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            CancelEventAction::make(),
            CancelEventAction::retry(),
        ];
    }
}
