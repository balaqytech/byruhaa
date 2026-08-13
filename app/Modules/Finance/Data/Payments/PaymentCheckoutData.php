<?php

namespace App\Modules\Finance\Data\Payments;

use DateTimeInterface;

final readonly class PaymentCheckoutData
{
    public function __construct(
        public string $paymentReference,
        public string $status,
        public string $checkoutUrl,
        public int $amountBaisa,
        public string $currency,
        public ?string $sessionId,
        public ?DateTimeInterface $expiresAt,
        public ?string $providerInvoice = null,
    ) {}
}
