<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\MinorProfilePurchasing;
use App\Modules\Identity\Data\MinorProfilePurchaseData;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Validation\ValidationException;

class MinorProfilePurchaseService implements MinorProfilePurchasing
{
    public function forGuardian(int $profileId, int $guardianId): MinorProfilePurchaseData
    {
        $profile = MinorProfile::query()
            ->with('familyMember')
            ->whereKey($profileId)
            ->whereHas('familyMember', fn ($query) => $query->where('customer_id', $guardianId))
            ->first();

        if (! $profile instanceof MinorProfile) {
            throw ValidationException::withMessages(['minor_profile_id' => 'The selected minor profile is not owned by this customer.']);
        }

        return $this->usableData($profile);
    }

    public function forProfile(int $profileId): MinorProfilePurchaseData
    {
        $profile = MinorProfile::query()->with('familyMember')->whereKey($profileId)->first();

        if (! $profile instanceof MinorProfile) {
            throw ValidationException::withMessages(['minor_profile_id' => 'The minor profile was not found.']);
        }

        return $this->usableData($profile);
    }

    public function mayPayDirectly(int $profileId, int $guardianId): bool
    {
        return $this->forGuardian($profileId, $guardianId)->directPaymentEnabled;
    }

    private function usableData(MinorProfile $profile): MinorProfilePurchaseData
    {
        if (! config('byruhaa.minor_accounts.enabled', true)) {
            throw ValidationException::withMessages(['minor_profile_id' => 'Minor accounts are currently disabled.']);
        }

        if (! $profile->isActive()) {
            throw ValidationException::withMessages(['minor_profile_id' => 'This minor profile is not allowed to place orders.']);
        }

        return new MinorProfilePurchaseData(
            $profile->id,
            $profile->family_member_id,
            (int) $profile->familyMember->customer_id,
            $profile->familyMember->name,
            $profile->member_code,
            $profile->direct_payment_enabled,
        );
    }
}
