<?php

namespace App\Filament\Resources\LedgerAccounts\Pages;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use Filament\Resources\Pages\ViewRecord;

class ViewLedgerAccount extends ViewRecord
{
    protected static string $resource = LedgerAccountResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.resources.ledger_accounts.label');
    }
}
