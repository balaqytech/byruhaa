<?php

namespace App\Filament\Resources\AffiliatePayoutRequests\Pages;

use App\Filament\Resources\AffiliatePayoutRequests\AffiliatePayoutRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAffiliatePayoutRequest extends EditRecord
{
    protected static string $resource = AffiliatePayoutRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
