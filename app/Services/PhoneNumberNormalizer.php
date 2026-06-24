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
            return (new PhoneNumber($phoneNumber, $country))->formatE164();
        } catch (Throwable) {
            //
        }

        try {
            return (new PhoneNumber($phoneNumber))->formatE164();
        } catch (Throwable) {
            return $phoneNumber;
        }
    }
}
