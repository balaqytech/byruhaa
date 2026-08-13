<?php

namespace App\Modules\Store\Listeners;

use App\Modules\Finance\Data\Payments\PaymentVerificationData;
use App\Modules\Finance\Events\PaymentSucceeded;
use App\Modules\Store\Actions\ConfirmStorePayment;

class HandlePaymentSucceeded
{
    public function __construct(private ConfirmStorePayment $confirmPayment) {}

    public function handle(PaymentSucceeded $event): void
    {
        $this->confirmPayment->execute(new PaymentVerificationData(
            $event->subjectType,
            $event->subjectReference,
            $event->paymentReference,
            $event->amountBaisa,
            $event->currency,
            'paid',
            $event->providerSessionId,
            $event->providerPaymentId,
            $event->providerInvoice,
        ));
    }
}
