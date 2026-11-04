<?php

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\Data\MinorProfilePurchaseData;
use Closure;

interface PosPurchasing
{
    public function resolve(string $token): MinorProfilePurchaseData;

    public function withVerifiedCard(string $token, Closure $purchase): mixed;
}
