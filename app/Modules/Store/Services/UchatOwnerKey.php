<?php

namespace App\Modules\Store\Services;

use Illuminate\Http\Exceptions\HttpResponseException;

class UchatOwnerKey
{
    public function forPhone(string $normalizedPhone): string
    {
        $secret = config('byruhaa.uchat.owner_key_secret');

        if (! is_string($secret) || trim($secret) === '') {
            throw new HttpResponseException(response()->json(['code' => 'uchat_not_configured', 'message' => 'UChat Store ownership is not configured.'], 503));
        }

        return hash_hmac('sha256', $normalizedPhone, $secret);
    }
}
