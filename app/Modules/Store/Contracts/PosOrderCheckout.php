<?php

namespace App\Modules\Store\Contracts;

use App\Modules\Store\Models\Order;

interface PosOrderCheckout
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, string $idempotencyKey, ?int $actorUserId = null, ?int $expectedTotalBaisa = null, ?string $posRequestHash = null): Order;

    /** @param array<string, mixed> $data */
    public function executeCashPos(array $data, string $idempotencyKey, int $actorUserId, int $expectedTotalBaisa, string $posRequestHash, int $cashReceivedBaisa, bool $guest): Order;
}
