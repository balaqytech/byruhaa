<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\CustomerIdentityResolver;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;

class PhoneCustomerIdentityResolver implements CustomerIdentityResolver
{
    public function __construct(private PhoneNumberNormalizer $normalizer) {}

    public function normalizePhone(string $phone): ?string
    {
        return $this->normalizer->normalize($phone);
    }

    public function findCustomerIdByPhone(string $normalizedPhone): ?int
    {
        $id = Customer::query()->where('phone_number', $normalizedPhone)->value('id');

        return is_numeric($id) ? (int) $id : null;
    }

    public function ownsMinorProfile(int $customerId, int $profileId): bool
    {
        return MinorProfile::query()->whereKey($profileId)->whereHas('familyMember', fn ($query) => $query->where('customer_id', $customerId))->exists();
    }
}
