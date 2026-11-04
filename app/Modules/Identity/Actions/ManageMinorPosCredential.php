<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Contracts\MinorProfilePurchasing;
use App\Modules\Identity\Contracts\PosPurchasing;
use App\Modules\Identity\Data\MinorProfilePurchaseData;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorPosCredential;
use App\Modules\Identity\Models\MinorProfile;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageMinorPosCredential implements PosPurchasing
{
    public function __construct(private MinorProfilePurchasing $profiles) {}

    public function issue(MinorProfile $profile): string
    {
        return DB::transaction(function () use ($profile): string {
            $lockedProfile = MinorProfile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();

            if ($lockedProfile->status !== MinorProfileStatus::Active) {
                throw ValidationException::withMessages(['card' => 'حساب القائد غير نشط.']);
            }
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $credential = MinorPosCredential::query()->firstOrNew(['minor_profile_id' => $profile->id]);
            $credential->forceFill([
                'token_hash' => hash('sha256', $token),
                'token_ciphertext' => $token,
                'card_issued_at' => now(),
                'card_revoked_at' => null,
            ])->save();

            return $token;
        });
    }

    public function revoke(MinorProfile $profile): void
    {
        DB::transaction(function () use ($profile): void {
            MinorProfile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
            MinorPosCredential::query()->where('minor_profile_id', $profile->id)->update([
                'token_hash' => null,
                'token_ciphertext' => null,
                'card_revoked_at' => now(),
            ]);
        });
    }

    public function resolve(string $token): MinorProfilePurchaseData
    {
        $credential = $this->credentialForToken($token);

        return $this->usableProfile($credential);
    }

    public function withVerifiedCard(string $token, Closure $purchase): mixed
    {
        return DB::transaction(function () use ($token, $purchase): mixed {
            $unlockedCredential = $this->credentialForToken($token);
            MinorProfile::query()->whereKey($unlockedCredential->minor_profile_id)->lockForUpdate()->firstOrFail();
            $credential = $this->credentialForToken($token, true);
            $profile = $this->usableProfile($credential);

            return $purchase($profile);
        });
    }

    private function credentialForToken(string $token, bool $lock = false): MinorPosCredential
    {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $token) !== 1) {
            throw ValidationException::withMessages(['card' => 'البطاقة غير صالحة.']);
        }

        $query = MinorPosCredential::query()->where('token_hash', hash('sha256', $token));
        $credential = ($lock ? $query->lockForUpdate() : $query)->first();

        if (! $credential instanceof MinorPosCredential || $credential->card_revoked_at !== null) {
            throw ValidationException::withMessages(['card' => 'البطاقة غير صالحة.']);
        }

        return $credential;
    }

    private function usableProfile(MinorPosCredential $credential): MinorProfilePurchaseData
    {
        return $this->profiles->forProfile($credential->minor_profile_id);
    }
}
