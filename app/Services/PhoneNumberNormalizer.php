<?php

namespace App\Services;

use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class PhoneNumberNormalizer
{
    public function normalize(?string $phoneNumber, string $country = 'OM'): ?string
    {
        if (blank($phoneNumber)) {
            return null;
        }

        try {
            return (string) new PhoneNumber($phoneNumber, $country);
        } catch (Throwable) {
            return $phoneNumber;
        }
    }
}
