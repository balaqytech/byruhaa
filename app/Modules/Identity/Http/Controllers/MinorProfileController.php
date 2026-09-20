<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Identity\Actions\CreateMinorProfile;
use App\Modules\Identity\Actions\IssueMinorProfileActivation;
use App\Modules\Identity\Actions\VerifyMinorProfile;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Http\Requests\StoreMinorProfileRequest;
use App\Modules\Identity\Http\Requests\VerifyMinorProfileRequest;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class MinorProfileController
{
    public function index(Request $request): View
    {
        /** @var Customer $guardian */
        $guardian = $request->user('customer');

        return view('pages.customer.minor-profiles.index', [
            'profiles' => $guardian->minorProfiles()->with('familyMember')->latest('minor_profiles.id')->get(),
            'familyMembers' => $guardian->familyMembers()->with('minorProfile')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreMinorProfileRequest $request, CreateMinorProfile $createMinorProfile): RedirectResponse
    {
        /** @var Customer $guardian */
        $guardian = $request->user('customer');
        $result = $createMinorProfile->execute($guardian, $request->validated(), $request->ip());

        return redirect()->route('customer.minor-profiles.index')->with([
            'success' => 'تم إنشاء الحساب. شارك رابط التفعيل مع الابن لاختيار كلمة مروره.',
            'activation_url' => URL::temporarySignedRoute('minor.activate', $result['profile']->activation_token_expires_at, [
                'minorProfile' => $result['profile']->id,
                'token' => $result['activation_token'],
            ]),
        ]);
    }

    public function activationLink(Request $request, MinorProfile $minorProfile, IssueMinorProfileActivation $issueActivation): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);
        $token = $issueActivation->execute($minorProfile, (int) $request->user('customer')->getAuthIdentifier(), $request->ip());

        return redirect()->route('customer.minor-profiles.index')->with([
            'success' => 'رابط التفعيل جاهز للمشاركة. لن تحتاج إلى رمز تحقق.',
            'activation_url' => URL::temporarySignedRoute('minor.activate', $minorProfile->refresh()->activation_token_expires_at, [
                'minorProfile' => $minorProfile->id,
                'token' => $token,
            ]),
        ]);
    }

    public function verify(VerifyMinorProfileRequest $request, MinorProfile $minorProfile, VerifyMinorProfile $verifyMinorProfile): RedirectResponse
    {
        $activationToken = $verifyMinorProfile->execute($minorProfile, (string) $request->validated('code'), $request->ip());
        $activationUrl = URL::temporarySignedRoute('minor.activate', now()->addDay(), [
            'minorProfile' => $minorProfile->id,
            'token' => $activationToken,
        ]);

        return redirect()->route('customer.minor-profiles.index')->with([
            'success' => 'تم توثيق وليّ الأمر. شارك رابط التفعيل مع القاصر.',
            'activation_url' => $activationUrl,
        ]);
    }

    public function suspend(Request $request, MinorProfile $minorProfile): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);
        abort_unless($minorProfile->status === MinorProfileStatus::Active, 404);

        $minorProfile->forceFill([
            'status' => MinorProfileStatus::Suspended,
            'suspended_at' => now(),
        ])->save();

        return back()->with('success', 'تم تعليق حساب القاصر.');
    }

    public function resume(Request $request, MinorProfile $minorProfile): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);

        abort_unless($minorProfile->status === MinorProfileStatus::Suspended, 404);

        $minorProfile->forceFill([
            'status' => MinorProfileStatus::Active,
            'suspended_at' => null,
        ])->save();

        return back()->with('success', 'تمت إعادة تفعيل حساب القاصر.');
    }

    public function toggleDirectPayment(Request $request, MinorProfile $minorProfile): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);

        abort_unless($minorProfile->status === MinorProfileStatus::Active, 404);
        $minorProfile->forceFill(['direct_payment_enabled' => ! $minorProfile->direct_payment_enabled])->save();

        return back()->with('success', $minorProfile->direct_payment_enabled ? 'تم السماح بالدفع المباشر.' : 'تم إيقاف الدفع المباشر.');
    }

    public function toggleWalletSpending(Request $request, MinorProfile $minorProfile): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);
        abort_unless(config('byruhaa.wallets.enabled', false), 404);

        abort_unless($minorProfile->status === MinorProfileStatus::Active, 404);

        /** @var Customer $customer */
        $customer = $request->user('customer');
        if ($customer->requiresPhoneVerification()) {
            throw ValidationException::withMessages(['phone' => 'Verify the guardian phone before enabling wallet spending.']);
        }

        $enabling = ! $minorProfile->wallet_spending_enabled;
        $minorProfile->forceFill(['wallet_spending_enabled' => $enabling])->save();

        if ($enabling) {
            $minorProfile->consents()->create([
                'purpose' => 'wallet_spending',
                'policy_version' => (string) config('byruhaa.wallets.consent_policy_version', 'wallet-spending-v1'),
                'policy_hash' => hash('sha256', (string) config('byruhaa.wallets.consent_policy_text', 'guardian-consent-wallet-spending')),
                'accepted_at' => now(),
                'accepted_ip' => $request->ip(),
            ]);
        }

        return back()->with('success', $minorProfile->wallet_spending_enabled ? 'تم السماح بالدفع من المحفظة.' : 'تم إيقاف الدفع من المحفظة.');
    }

    public function requestDeletion(Request $request, MinorProfile $minorProfile, WalletService $wallets): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);

        DB::transaction(function () use ($minorProfile, $wallets): void {
            $minorProfile = MinorProfile::query()
                ->whereKey($minorProfile->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $wallets->canClose($minorProfile->id)) {
                throw ValidationException::withMessages([
                    'wallet' => 'Empty or cancel the child wallet before requesting account deletion.',
                ]);
            }

            $minorProfile->forceFill([
                'status' => MinorProfileStatus::DeletionRequested,
                'deletion_requested_at' => now(),
            ])->save();
        });

        return back()->with('success', 'تم تسجيل طلب حذف الحساب، وسيتم التواصل معك عبر القنوات الرسمية.');
    }

    private function assertOwnedBy(Request $request, MinorProfile $minorProfile): void
    {
        abort_unless(
            (int) $minorProfile->familyMember()->value('customer_id') === (int) $request->user('customer')->getAuthIdentifier(),
            404,
        );
    }
}
