<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetMinorWalletSpending
{
    public function execute(MinorProfile $profile, bool $enabled, ?string $ipAddress = null): MinorProfile
    {
        return DB::transaction(function () use ($profile, $enabled, $ipAddress): MinorProfile {
            $profile = MinorProfile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();

            if ($enabled && $profile->status !== MinorProfileStatus::Active) {
                throw ValidationException::withMessages([
                    'minor_profile_id' => 'Wallet spending can only be enabled for an active minor profile.',
                ]);
            }

            if ($profile->wallet_spending_enabled === $enabled) {
                return $profile;
            }

            $profile->forceFill(['wallet_spending_enabled' => $enabled])->save();

            if ($enabled) {
                $profile->consents()->create([
                    'purpose' => 'wallet_spending',
                    'policy_version' => (string) config('byruhaa.wallets.consent_policy_version', 'wallet-spending-v1'),
                    'policy_hash' => hash('sha256', (string) config('byruhaa.wallets.consent_policy_text', 'guardian-consent-wallet-spending')),
                    'accepted_at' => now(),
                    'accepted_ip' => $ipAddress,
                ]);
            }

            return $profile->refresh();
        });
    }
}
