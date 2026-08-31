<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerifyMinorProfile
{
    public function execute(MinorProfile $profile, string $code, ?string $ipAddress = null): string
    {
        return DB::transaction(function () use ($profile, $code, $ipAddress): string {
            $profile = MinorProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if ($profile->status !== MinorProfileStatus::PendingGuardianVerification) {
                throw ValidationException::withMessages(['code' => 'This verification is no longer available.']);
            }

            $verification = $profile->verifications()
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($verification === null || $verification->expires_at->isPast() || $verification->attempts >= 5) {
                throw ValidationException::withMessages(['code' => 'The verification code has expired.']);
            }

            $verification->increment('attempts');

            if (! Hash::check($code, $verification->code_hash)) {
                throw ValidationException::withMessages(['code' => 'The verification code is incorrect.']);
            }

            $verification->forceFill(['consumed_at' => now()])->save();
            $activationToken = Str::random(64);

            $profile->forceFill([
                'status' => MinorProfileStatus::PendingChildActivation,
                'activation_token_hash' => Hash::make($activationToken),
                'activation_token_expires_at' => now()->addDay(),
            ])->save();

            $profile->consents()->create([
                'purpose' => 'store_purchase',
                'policy_version' => (string) config('byruhaa.minor_accounts.policy_version', 'phase-2a'),
                'policy_hash' => hash('sha256', (string) config('byruhaa.minor_accounts.policy_text', 'minor-store-purchase')),
                'accepted_at' => now(),
                'accepted_ip' => $ipAddress,
            ]);

            return $activationToken;
        });
    }
}
