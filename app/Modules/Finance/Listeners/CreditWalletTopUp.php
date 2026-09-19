<?php

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Events\PaymentSucceeded;
use App\Modules\Finance\Models\Payment;

class CreditWalletTopUp
{
    public function __construct(private WalletService $wallets) {}

    public function handle(PaymentSucceeded $event): void
    {
        if ($event->subjectType !== 'wallet_topup') {
            return;
        }

        $payment = Payment::query()->where('reference', $event->paymentReference)->first();

        if ($payment instanceof Payment) {
            $this->wallets->creditTopUp($payment);
        }
    }
}
