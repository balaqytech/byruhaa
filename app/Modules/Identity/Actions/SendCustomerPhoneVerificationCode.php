<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\CustomerPhoneVerification;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SendCustomerPhoneVerificationCode
{
    public function __construct(private ByruhaaWebhookSender $sender) {}

    public function execute(Customer $customer): string
    {
        $latest = $customer->phoneVerifications()->latest('id')->first();
        $cooldown = (int) config('byruhaa.wallets.phone_otp_resend_seconds', 60);

        if ($latest instanceof CustomerPhoneVerification && $latest->created_at?->gt(now()->subSeconds($cooldown))) {
            throw ValidationException::withMessages(['phone' => 'Wait before requesting another verification code.']);
        }

        $code = (string) random_int(100000, 999999);
        $verification = $customer->phoneVerifications()->create([
            'phone_number' => $customer->phone_number,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('byruhaa.wallets.phone_otp_expiry_minutes', 10)),
        ]);

        $this->sender->sendUchatCustomerPhoneVerificationCode($customer, $code, $verification->expires_at, (string) $verification->getKey());

        return $code;
    }
}
