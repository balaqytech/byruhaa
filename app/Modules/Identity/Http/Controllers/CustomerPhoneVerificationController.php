<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\SendCustomerPhoneVerificationCode;
use App\Modules\Identity\Actions\VerifyCustomerPhone;
use App\Modules\Identity\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerPhoneVerificationController
{
    public function send(Request $request, SendCustomerPhoneVerificationCode $sendCode): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        try {
            $sendCode->execute($customer);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('phone_verification_sent', true);
    }

    public function verify(Request $request, VerifyCustomerPhone $verifyPhone): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        try {
            $verifyPhone->execute($customer, (string) $validated['code']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('phone_verified', true);
    }
}
