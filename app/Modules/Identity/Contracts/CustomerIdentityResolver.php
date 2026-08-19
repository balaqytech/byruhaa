<?php

namespace App\Modules\Identity\Contracts;

interface CustomerIdentityResolver
{
    public function normalizePhone(string $phone): ?string;

    public function findCustomerIdByPhone(string $normalizedPhone): ?int;
}
