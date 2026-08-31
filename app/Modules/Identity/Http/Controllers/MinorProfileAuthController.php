<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Http\Requests\ActivateMinorProfileRequest;
use App\Modules\Identity\Http\Requests\MinorProfileLoginRequest;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MinorProfileAuthController
{
    public function showLogin(): View
    {
        return view('pages.minor.auth.login');
    }

    public function login(MinorProfileLoginRequest $request): RedirectResponse
    {
        $profile = MinorProfile::query()
            ->where('member_code', strtoupper(trim((string) $request->validated('member_code'))))
            ->first();

        if (! $profile instanceof MinorProfile || ! Hash::check((string) $request->validated('password'), $profile->password)) {
            throw ValidationException::withMessages(['login' => 'رمز الدخول أو كلمة المرور غير صحيحة.']);
        }

        if ($profile->status !== MinorProfileStatus::Active) {
            throw ValidationException::withMessages(['login' => 'هذا الحساب غير مفعّل أو غير متاح حاليًا.']);
        }

        Auth::guard('customer')->logout();
        Auth::guard('minor-profile')->login($profile, false);
        $request->session()->forget(['store_cart_token', 'store_checkout_idempotency_keys', 'store_checkout_idempotency_key']);
        $request->session()->regenerate();

        return redirect()->intended(route('minor.dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('minor-profile')->logout();
        session()->forget(['store_cart_token', 'store_checkout_idempotency_keys', 'store_checkout_idempotency_key']);

        return redirect()->route('minor.login');
    }

    public function showActivation(MinorProfile $minorProfile, string $token): View
    {
        $this->assertActivationToken($minorProfile, $token);

        return view('pages.minor.auth.activate', compact('minorProfile', 'token'));
    }

    public function activate(ActivateMinorProfileRequest $request, MinorProfile $minorProfile, string $token): RedirectResponse
    {
        $token = (string) ($request->validated('token') ?: $token);
        $this->assertActivationToken($minorProfile, $token);

        $minorProfile->forceFill([
            'password' => (string) $request->validated('password'),
            'status' => MinorProfileStatus::Active,
            'activated_at' => now(),
            'activation_token_hash' => null,
            'activation_token_expires_at' => null,
        ])->save();

        return redirect()->route('minor.login')->with('success', 'تم تفعيل الحساب. يمكنك تسجيل الدخول الآن.');
    }

    private function assertActivationToken(MinorProfile $profile, string $token): void
    {
        abort_unless(
            $profile->status === MinorProfileStatus::PendingChildActivation
                && $profile->activation_token_expires_at?->isFuture()
                && filled($profile->activation_token_hash)
                && Hash::check($token, $profile->activation_token_hash),
            404,
        );
    }
}
