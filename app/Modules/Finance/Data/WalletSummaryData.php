<?php

namespace App\Modules\Finance\Data;

final readonly class WalletSummaryData
{
    public function __construct(
        public int $walletId,
        public int $minorProfileId,
        public int $balanceBaisa,
        public string $currency,
        public string $status,
    ) {}
}
