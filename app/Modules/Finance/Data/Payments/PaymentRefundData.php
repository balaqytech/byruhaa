<?php

namespace App\Modules\Finance\Data\Payments;

final readonly class PaymentRefundData
{
    public function __construct(
        public string $paymentReference,
        public string $refundReference,
        public string $status,
        public int $amountBaisa,
        public string $currency,
    ) {}
}
