<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\CustomerIdentityResolver;
use App\Modules\Identity\Models\Customer;

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
}
