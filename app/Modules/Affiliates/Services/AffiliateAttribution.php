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

        return $this->validPayload($payload) ? $payload : null;
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

        if (! is_array($payload) || ! $this->validPayload($payload)) {
            Cookie::expire($this->cookieName());

            return;
        }

        $request->session()->put($this->sessionKey(), $payload);
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
     */
    private function validPayload(array $payload): bool
    {
        if (! isset($payload['affiliate_id'], $payload['code'], $payload['name'], $payload['captured_at'], $payload['expires_at'])) {
            return false;
        }

        try {
            return Carbon::parse((string) $payload['expires_at'])->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }
}
