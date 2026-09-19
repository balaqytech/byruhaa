<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueMinorProfileActivation
{
    public function execute(MinorProfile $profile, int $guardianId, ?string $ipAddress = null): string
    {
        if (! config('byruhaa.minor_accounts.enabled', true)) {
            throw ValidationException::withMessages(['minor_accounts' => 'Minor accounts are currently disabled.']);
        }

        return DB::transaction(function () use ($profile, $guardianId, $ipAddress): string {
            $profile = MinorProfile::query()->whereKey($profile->id)
                ->whereHas('familyMember', fn ($query) => $query->where('customer_id', $guardianId))
                ->lockForUpdate()->first();

            if ($profile === null) {
                throw ValidationException::withMessages(['minor_profile_id' => 'The selected minor profile is not owned by this customer.']);
            }
            if (! in_array($profile->status, [MinorProfileStatus::PendingGuardianVerification, MinorProfileStatus::PendingChildActivation], true)) {
                throw ValidationException::withMessages(['minor_profile_id' => 'This account is not awaiting activation.']);
            }

            $token = Str::random(64);
            $profile->forceFill([
                'status' => MinorProfileStatus::PendingChildActivation,
                'activation_token_hash' => Hash::make($token),
                'activation_token_expires_at' => now()->addDay(),
            ])->save();
            $profile->verifications()->whereNull('consumed_at')->update(['consumed_at' => now()]);
            $profile->consents()->firstOrCreate(['purpose' => 'store_purchase'], [
                'policy_version' => (string) config('byruhaa.minor_accounts.policy_version', 'phase-2a'),
                'policy_hash' => hash('sha256', (string) config('byruhaa.minor_accounts.policy_text', 'minor-store-purchase')),
                'accepted_at' => now(),
                'accepted_ip' => $ipAddress,
            ]);

            return $token;
        });
    }
}
