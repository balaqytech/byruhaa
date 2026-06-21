<?php

namespace App\Filament\Resources\AffiliatePayoutRequests\Pages;

use App\Filament\Resources\AffiliatePayoutRequests\AffiliatePayoutRequestResource;
use App\Filament\Resources\AffiliatePayoutRequests\Tables\AffiliatePayoutRequestsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewAffiliatePayoutRequest extends ViewRecord
{
    protected static string $resource = AffiliatePayoutRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AffiliatePayoutRequestsTable::approveAction(),
            AffiliatePayoutRequestsTable::rejectAction(),
            AffiliatePayoutRequestsTable::markPaidAction(),
        ];
    }
}
