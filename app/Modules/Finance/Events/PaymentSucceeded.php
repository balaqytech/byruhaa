<?php

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Data\Payments\PaymentVerificationData;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class PaymentSucceeded implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $subjectType,
        public string $subjectReference,
        public string $paymentReference,
        public int $amountBaisa,
        public string $currency,
        public ?string $providerSessionId,
        public ?string $providerPaymentId,
        public ?string $providerInvoice,
    ) {}

    public static function fromVerification(PaymentVerificationData $verification): self
    {
        return new self(
            $verification->subjectType,
            $verification->subjectReference,
            $verification->paymentReference,
            $verification->amountBaisa,
            $verification->currency,
            $verification->providerSessionId,
            $verification->providerPaymentId,
            $verification->providerInvoice,
        );
    }
}
