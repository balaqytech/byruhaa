<?php

namespace App\Filament\Resources\LedgerTransactions\Pages;

use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ViewLedgerTransaction extends ViewRecord
{
    protected static string $resource = LedgerTransactionResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getInfolistContentComponent()->columnSpanFull(),
            $this->getRelationManagersContentComponent()->columnSpanFull(),
        ]);
    }
}
