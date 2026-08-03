<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AffiliateAuthController extends Controller
{
    use PasswordValidationRules;

    public function create(): View|RedirectResponse
    {
        return $this->redirectAuthenticatedAffiliate() ?? view('pages.affiliate.auth.login');
    }

    public function store(Request $request, PhoneNumberNormalizer $phoneNumberNormalizer): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable'],
        ]);

        $login = (string) $validated['login'];
        $affiliate = str_contains($login, '@')
            ? Affiliate::query()->where('email', $login)->first()
            : Affiliate::query()->where('phone_number', $phoneNumberNormalizer->normalize($login))->first();

        if (! $affiliate instanceof Affiliate || ! Hash::check((string) $validated['password'], $affiliate->password)) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        if (in_array($affiliate->status, [AffiliateStatus::Rejected, AffiliateStatus::Suspended], true)) {
            throw ValidationException::withMessages([
                'login' => __('ui.affiliates.account_not_available'),
            ]);
        }

        Auth::guard('affiliate')->login($affiliate, $request->boolean('remember'));
        $request->session()->regenerate();

        return $affiliate->status === AffiliateStatus::Approved
            ? redirect()->intended(route('affiliate.dashboard', absolute: false))
            : redirect()->route('affiliate.pending');
    }

    public function register(): View|RedirectResponse
    {
        return $this->redirectAuthenticatedAffiliate() ?? view('pages.affiliate.auth.register');
    }

    public function createAffiliate(Request $request, PhoneNumberNormalizer $phoneNumberNormalizer): RedirectResponse
    {
        $input = $request->all();
        $input['phone_number'] = $phoneNumberNormalizer->normalize($input['phone_number'] ?? null);

        validator($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(Affiliate::class)],
            'phone_number' => ['required', 'string', 'phone:OM', Rule::unique(Affiliate::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        $affiliate = Affiliate::create([
            'name' => $input['name'],
            'email' => $input['email'] ?: null,
            'phone_number' => $input['phone_number'],
            'password' => $input['password'],
            'status' => AffiliateStatus::Pending,
        ]);

        Auth::guard('affiliate')->login($affiliate);
        $request->session()->regenerate();

        return redirect()->route('affiliate.pending');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('affiliate')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login');
    }

    private function redirectAuthenticatedAffiliate(): ?RedirectResponse
    {
        $affiliate = Auth::guard('affiliate')->user();

        if (! $affiliate instanceof Affiliate) {
            return null;
        }

        return $affiliate->status === AffiliateStatus::Approved
            ? redirect()->route('affiliate.dashboard')
            : redirect()->route('affiliate.pending');
    }
}
