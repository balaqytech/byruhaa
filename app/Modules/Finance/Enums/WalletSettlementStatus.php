<?php

namespace App\Modules\Finance\Enums;

enum WalletSettlementStatus: string
{
    case Eligible = 'eligible';
    case Transferred = 'transferred';
    case Reversed = 'reversed';
}
