<?php

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewLedgerTransaction extends ViewRecord
{
    protected static string $resource = LedgerTransactionResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.resources.ledger_transactions.label');
    }
}
