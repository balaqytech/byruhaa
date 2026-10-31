<?php

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Models\WalletWithdrawalRequest;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WalletWithdrawalStateChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public WalletWithdrawalRequest $withdrawal) {}
}
