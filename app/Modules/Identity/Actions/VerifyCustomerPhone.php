<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\CustomerPhoneVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyCustomerPhone
{
    public function execute(Customer $customer, string $code): void
    {
        $error = DB::transaction(function () use ($customer, $code): ?string {
            $customer = Customer::query()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            $verification = $customer->phoneVerifications()
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $verification instanceof CustomerPhoneVerification || $verification->phone_number !== $customer->phone_number) {
                return 'missing';
            }

            if ($verification->expires_at->isPast() || $verification->attempts >= (int) config('byruhaa.wallets.phone_otp_max_attempts', 5)) {
                return 'expired';
            }

            $verification->increment('attempts');

            if (! Hash::check($code, $verification->code_hash)) {
                return 'incorrect';
            }

            $verification->forceFill(['consumed_at' => now()])->save();
            $customer->forceFill(['phone_verified_at' => now()])->save();

            return null;
        });

        if ($error === null) {
            return;
        }

        throw ValidationException::withMessages([
            'code' => match ($error) {
                'incorrect' => 'The verification code is incorrect.',
                'expired' => 'The verification code has expired.',
                default => 'Request a new verification code.',
            },
        ]);
    }
}
