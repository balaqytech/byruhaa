<?php

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Enums\WalletMovementType;
use App\Modules\Finance\Events\WalletMovementPosted;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Notifications\MinorWalletTopUpNotification;

class NotifyMinorWalletTopUp
{
    public function handle(WalletMovementPosted $event): void
    {
        $movement = $event->movement;

        if ($movement->type !== WalletMovementType::TopUp->value) {
            return;
        }

        $movement->loadMissing('wallet.minorProfile');
        $profile = $movement->wallet->minorProfile;

        if (! $profile instanceof MinorProfile) {
            return;
        }

        $profile->notify(new MinorWalletTopUpNotification(
            $movement->credit_baisa,
            $movement->balance_after_baisa,
            $movement->wallet->currency,
            route('minor.dashboard').'#wallet',
        ));
    }
}
