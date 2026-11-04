<?php

namespace App\Modules\Finance\Data;

final readonly class WalletSpendData
{
    public function __construct(
        public int $walletId,
        public int $movementId,
        public int $amountBaisa,
        public int $balanceAfterBaisa,
        public string $currency,
    ) {}
}
