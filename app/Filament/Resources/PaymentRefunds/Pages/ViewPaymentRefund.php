<?php

namespace App\Filament\Resources\PaymentRefunds\Pages;

use App\Filament\Resources\PaymentRefunds\PaymentRefundResource;
use App\Filament\Resources\PaymentRefunds\Tables\PaymentRefundsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentRefund extends ViewRecord
{
    protected static string $resource = PaymentRefundResource::class;

    protected function getHeaderActions(): array
    {
        return [PaymentRefundsTable::confirmManualRefundAction()];
    }
}
