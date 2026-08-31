<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\CreateMinorProfile;
use App\Modules\Identity\Actions\SendMinorProfileVerificationCode;
use App\Modules\Identity\Actions\VerifyMinorProfile;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Http\Requests\StoreMinorProfileRequest;
use App\Modules\Identity\Http\Requests\VerifyMinorProfileRequest;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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

    public function store(StoreMinorProfileRequest $request, CreateMinorProfile $createMinorProfile, SendMinorProfileVerificationCode $sendCode): RedirectResponse
    {
        /** @var Customer $guardian */
        $guardian = $request->user('customer');
        $result = $createMinorProfile->execute($guardian, $request->validated());
        $sendCode->execute($result['profile'], $result['code']);

        return redirect()->route('customer.minor-profiles.index')->with([
            'success' => 'تم إنشاء ملف القاصر وإرسال رمز التحقق إلى وليّ الأمر.',
            'verification_profile_id' => $result['profile']->id,
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

    public function requestDeletion(Request $request, MinorProfile $minorProfile): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);

        $minorProfile->forceFill([
            'status' => MinorProfileStatus::DeletionRequested,
            'deletion_requested_at' => now(),
        ])->save();

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
