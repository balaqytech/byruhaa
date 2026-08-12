<?php

namespace App\Modules\Affiliates\Services;

use App\Modules\Affiliates\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;

class AffiliateAttribution
{
    /**
     * @return array{affiliate_id: int, code: string, name: string, captured_at: string, expires_at: string}
     */
    public function store(Affiliate $affiliate, Request $request): array
    {
        $capturedAt = now();
        $expiresAt = $capturedAt->copy()->addDays((int) config('affiliate.attribution_days', 90));
        $payload = [
            'affiliate_id' => $affiliate->id,
            'code' => $affiliate->code,
            'name' => $affiliate->name,
            'captured_at' => $capturedAt->toISOString(),
            'expires_at' => $expiresAt->toISOString(),
        ];

        $request->session()->put($this->sessionKey(), $payload);
        Cookie::queue($this->cookieName(), json_encode($payload, JSON_THROW_ON_ERROR), $this->attributionMinutes());

        return $payload;
    }

    /**
     * @return array{affiliate_id: int, code: string, name: string, captured_at: string, expires_at: string}|null
     */
    public function current(?Request $request = null): ?array
    {
        $request ??= request();
        $payload = $request->session()->get($this->sessionKey());

        if (! is_array($payload)) {
            return null;
        }

        return $this->normalizePayload($payload);
    }

    public function hydrateFromCookie(Request $request): void
    {
        if ($this->current($request) !== null) {
            return;
        }

        $cookie = $request->cookie($this->cookieName());

        if (! is_string($cookie) || $cookie === '') {
            return;
        }

        $payload = json_decode($cookie, true);

        if (! is_array($payload)) {
            Cookie::expire($this->cookieName());

            return;
        }

        $normalizedPayload = $this->normalizePayload($payload);

        if ($normalizedPayload === null) {
            Cookie::expire($this->cookieName());

            return;
        }

        $request->session()->put($this->sessionKey(), $normalizedPayload);
    }

    public function forget(Request $request): void
    {
        $request->session()->forget($this->sessionKey());
        Cookie::expire($this->cookieName());
    }

    private function attributionMinutes(): int
    {
        return (int) config('affiliate.attribution_days', 90) * 24 * 60;
    }

    private function cookieName(): string
    {
        return (string) config('affiliate.attribution_cookie', 'affiliate_referral');
    }

    private function sessionKey(): string
    {
        return 'affiliate.referral';
    }

    /**
     * @param  array<mixed>  $payload
     * @return array{affiliate_id: int, code: string, name: string, captured_at: string, expires_at: string}|null
     */
    private function normalizePayload(array $payload): ?array
    {
        if (! isset($payload['affiliate_id'], $payload['code'], $payload['name'], $payload['captured_at'], $payload['expires_at'])
            || (! is_int($payload['affiliate_id']) && ! ctype_digit((string) $payload['affiliate_id']))
            || ! is_string($payload['code'])
            || ! is_string($payload['name'])
            || ! is_string($payload['captured_at'])
            || ! is_string($payload['expires_at'])) {
            return null;
        }

        try {
            if (! Carbon::parse($payload['expires_at'])->isFuture()) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return [
            'affiliate_id' => (int) $payload['affiliate_id'],
            'code' => $payload['code'],
            'name' => $payload['name'],
            'captured_at' => $payload['captured_at'],
            'expires_at' => $payload['expires_at'],
        ];
    }
}
