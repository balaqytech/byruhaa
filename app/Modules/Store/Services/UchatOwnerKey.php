<?php

namespace App\Modules\Store\Services;

class UchatOwnerKey
{
    public function forPhone(string $normalizedPhone): string
    {
        $secret = config('byruhaa.uchat.owner_key_secret');

        if (! is_string($secret) || trim($secret) === '') {
            throw new \RuntimeException('UChat Store owner key secret is not configured.');
        }

        return hash_hmac('sha256', $normalizedPhone, $secret);
    }
}
