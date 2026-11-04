<?php

namespace App\Modules\Identity\Data;

final readonly class MinorOrderStatusData
{
    public function __construct(
        public string $reference,
        public string $status,
        public string $statusLabel,
        public int $totalBaisa,
        public string $currency,
        public string $url,
    ) {}
}
