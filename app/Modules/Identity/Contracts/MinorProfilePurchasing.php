<?php

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\Data\MinorProfilePurchaseData;

interface MinorProfilePurchasing
{
    public function forGuardian(int $profileId, int $guardianId): MinorProfilePurchaseData;

    public function forProfile(int $profileId): MinorProfilePurchaseData;

    public function mayPayDirectly(int $profileId, int $guardianId): bool;
}
