<?php

namespace App\Modules\Finance\Data\Payments;

final readonly class PaymentVerificationData
{
    public function __construct(
        public string $subjectType,
        public string $subjectReference,
        public string $paymentReference,
        public int $amountBaisa,
        public string $currency,
        public string $status,
        public ?string $providerSessionId,
        public ?string $providerPaymentId,
        public ?string $providerInvoice,
    ) {}
}
