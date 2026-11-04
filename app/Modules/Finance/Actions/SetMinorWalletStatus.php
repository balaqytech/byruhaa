<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetMinorWalletStatus
{
    public function __construct(private WalletService $wallets) {}

    public function execute(MinorProfile $profile, string $status): Wallet
    {
        if (! in_array($status, ['active', 'suspended'], true)) {
            throw ValidationException::withMessages(['wallet' => 'The requested wallet status is invalid.']);
        }

        if ($status === 'active' && $profile->status !== MinorProfileStatus::Active) {
            throw ValidationException::withMessages([
                'minor_profile_id' => 'A wallet can only be activated for an active minor profile.',
            ]);
        }

        $wallet = $this->wallets->walletForMinorProfile($profile->id);

        return DB::transaction(function () use ($wallet, $status): Wallet {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($wallet->status === 'closed') {
                throw ValidationException::withMessages(['wallet' => 'A closed wallet cannot be changed through this endpoint.']);
            }

            if ($wallet->status !== $status) {
                $wallet->forceFill(['status' => $status])->save();
            }

            return $wallet->refresh();
        });
    }
}
