<?php

namespace App\Modules\Finance\Data\Payments;

use DateTimeInterface;

final readonly class PaymentCheckoutRequest
{
    /**
     * @param  array<int, array{name: string, quantity: int, unit_amount: int}>  $products
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $subjectType,
        public string $subjectReference,
        public int $amountBaisa,
        public string $currency,
        public array $products,
        public string $successUrl,
        public string $cancelUrl,
        public array $metadata = [],
        public ?DateTimeInterface $expiresAt = null,
    ) {}
}
