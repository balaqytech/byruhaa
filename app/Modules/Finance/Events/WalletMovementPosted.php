<?php

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Models\WalletMovement;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WalletMovementPosted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public WalletMovement $movement) {}
}
