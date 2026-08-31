<?php

namespace App\Modules\Identity\Data;

final readonly class MinorProfilePurchaseData
{
    public function __construct(
        public int $profileId,
        public int $familyMemberId,
        public int $guardianId,
        public string $name,
        public string $memberCode,
        public bool $directPaymentEnabled,
    ) {}
}
