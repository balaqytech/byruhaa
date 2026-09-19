<?php

namespace App\Modules\Finance\Enums;

enum WalletTopUpStatus: string
{
    case Pending = 'pending';
    case Credited = 'credited';
    case Refunding = 'refunding';
    case Refunded = 'refunded';
}
