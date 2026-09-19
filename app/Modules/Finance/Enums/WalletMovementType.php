<?php

namespace App\Modules\Finance\Enums;

enum WalletMovementType: string
{
    case TopUp = 'top_up';
    case TopUpRefund = 'top_up_refund';
    case Purchase = 'purchase';
    case PurchaseReversal = 'purchase_reversal';
}
