<?php

namespace App\Http\Controllers;

use App\Http\Requests\UchatMinorActivationRequest;
use App\Http\Requests\UchatMinorProfileRequest;
use App\Http\Requests\UchatVerificationRequest;
use App\Modules\Identity\Actions\CreateMinorProfile;
use App\Modules\Identity\Actions\IssueMinorProfileActivation;
use App\Modules\Identity\Actions\SendCustomerPhoneVerificationCode;
use App\Modules\Identity\Actions\SendMinorProfileVerificationCode;
use App\Modules\Identity\Actions\VerifyCustomerPhone;
use App\Modules\Identity\Actions\VerifyMinorProfile;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Http\Requests\UchatIdentityRequest;
use App\Modules\Store\Http\Requests\UchatRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class UchatAccountController extends Controller
{
    public function account(UchatIdentityRequest $request): JsonResponse
    {
        $guardian = $this->guardian($request);

        return response()->json(['data' => [
            'customer_id' => $guardian->id,
            'name' => $guardian->name,
            'phone_verified' => $guardian->hasVerifiedPhone(),
            'phone_verified_at' => $guardian->phone_verified_at?->toJSON(),
            'minor_accounts_enabled' => (bool) config('byruhaa.minor_accounts.enabled', true),
            'wallets_enabled' => (bool) config('byruhaa.wallets.enabled', false),
            'manage_accounts_url' => route('customer.minor-profiles.index'),
            'consent_policy_version' => config('byruhaa.minor_accounts.policy_version', 'phase-2a'),
            'consent_policy_text' => config('byruhaa.minor_accounts.policy_text', 'minor-store-purchase'),
        ]]);
    }

    public function index(UchatIdentityRequest $request): JsonResponse
    {
        $profiles = $this->guardian($request)->minorProfiles()->with('familyMember')->orderBy('minor_profiles.id')->get();

        return response()->json(['data' => $profiles->map(fn (MinorProfile $profile): array => $this->profileData($profile))]);
    }

    public function store(UchatMinorProfileRequest $request, CreateMinorProfile $create): JsonResponse
    {
        $guardian = $this->guardian($request);
        $this->requireMinorAccounts();
        $created = false;
        $activationToken = null;
        $profile = DB::transaction(function () use ($guardian, $request, $create, &$created, &$activationToken): MinorProfile {
            $familyMember = $guardian->familyMembers()->whereKey($request->integer('family_member_id'))->lockForUpdate()->first();
            if ($familyMember === null) {
                throw ValidationException::withMessages(['family_member_id' => 'The selected family member was not found.']);
            }
            $existing = $familyMember->minorProfile()->first();
            if ($existing !== null) {
                return $existing;
            }
            $result = $create->execute($guardian, ['family_member_id' => $familyMember->id], $request->ip());
            $activationToken = $result['activation_token'];
            $created = true;

            return $result['profile'];
        });

        return response()->json([
            'data' => $this->profileData($profile->load('familyMember')),
            'created' => $created,
            'activation_url' => $activationToken === null ? null : URL::temporarySignedRoute('minor.activate', $profile->activation_token_expires_at, ['minorProfile' => $profile->id, 'token' => $activationToken]),
            'expires_at' => $profile->activation_token_expires_at?->toJSON(),
        ], $created ? 201 : 200);
    }

    public function activationLink(UchatMinorActivationRequest $request, int $minorProfile, IssueMinorProfileActivation $issueActivation): JsonResponse
    {
        $guardian = $this->guardian($request);
        $profile = $this->ownedProfile($guardian, $minorProfile);
        $token = $issueActivation->execute($profile, $guardian->id, $request->ip());
        $profile->refresh();

        return response()->json([
            'data' => $this->profileData($profile->load('familyMember')),
            'activation_url' => URL::temporarySignedRoute('minor.activate', $profile->activation_token_expires_at, ['minorProfile' => $profile->id, 'token' => $token]),
            'expires_at' => $profile->activation_token_expires_at?->toJSON(),
        ]);
    }

    public function sendPhoneCode(UchatIdentityRequest $request, SendCustomerPhoneVerificationCode $send): JsonResponse
    {
        $guardian = $this->guardian($request);
        $this->requireDeliveryConfiguration();
        if ($guardian->hasVerifiedPhone()) {
            return response()->json(['status' => 'already_verified']);
        }
        DB::transaction(function () use ($guardian, $send): void {
            $guardian = Customer::query()->lockForUpdate()->findOrFail($guardian->id);
            $send->execute($guardian);
        });

        return response()->json(['status' => 'verification_requested'], 202);
    }

    public function verifyPhone(UchatVerificationRequest $request, VerifyCustomerPhone $verify): JsonResponse
    {
        $verify->execute($this->guardian($request), (string) $request->validated('code'));

        return response()->json(['status' => 'verified']);
    }

    public function resend(UchatIdentityRequest $request, int $minorProfile, SendMinorProfileVerificationCode $send): JsonResponse
    {
        $guardian = $this->guardian($request);
        $this->requireMinorAccounts();
        $this->requireDeliveryConfiguration();
        DB::transaction(function () use ($guardian, $minorProfile, $send): void {
            $profile = $this->ownedProfile($guardian, $minorProfile, true);
            if ($profile->status !== MinorProfileStatus::PendingGuardianVerification) {
                throw ValidationException::withMessages(['code' => 'This verification is no longer available.']);
            }
            $latest = $profile->verifications()->latest('id')->first();
            if ($latest?->created_at?->gt(now()->subSeconds(60))) {
                throw ValidationException::withMessages(['code' => 'Wait before requesting another verification code.']);
            }
            $profile->verifications()->whereNull('consumed_at')->update(['consumed_at' => now()]);
            $profile->forceFill(['status' => MinorProfileStatus::PendingGuardianVerification, 'activation_token_hash' => null, 'activation_token_expires_at' => null])->save();
            $code = (string) random_int(100000, 999999);
            $verification = $profile->verifications()->create([
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes((int) config('byruhaa.minor_accounts.otp_expiry_minutes', 10)),
            ]);
            DB::afterCommit(fn () => $send->execute($profile, $code, (string) $verification->id));
        });

        return response()->json(['status' => 'verification_requested'], 202);
    }

    public function verify(UchatVerificationRequest $request, int $minorProfile, VerifyMinorProfile $verify): JsonResponse
    {
        $this->requireMinorAccounts();
        $profile = $this->ownedProfile($this->guardian($request), $minorProfile);
        $token = $verify->execute($profile, (string) $request->validated('code'), $request->ip());
        $profile->refresh();

        return response()->json([
            'data' => $this->profileData($profile->load('familyMember')),
            'activation_url' => URL::temporarySignedRoute('minor.activate', $profile->activation_token_expires_at, ['minorProfile' => $profile->id, 'token' => $token]),
            'expires_at' => $profile->activation_token_expires_at?->toJSON(),
        ]);
    }

    private function guardian(UchatRequest $request): Customer
    {
        $guardian = Customer::query()->find($request->customerId());
        if (! $guardian instanceof Customer) {
            throw ValidationException::withMessages(['phone' => 'A guardian account is required for a minor profile.']);
        }

        return $guardian;
    }

    private function ownedProfile(Customer $guardian, int $id, bool $lock = false): MinorProfile
    {
        $profile = MinorProfile::query()->whereKey($id)
            ->whereHas('familyMember', fn ($query) => $query->where('customer_id', $guardian->id))
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();
        if (! $profile instanceof MinorProfile) {
            throw ValidationException::withMessages(['minor_profile_id' => 'The selected minor profile is not owned by this customer.']);
        }

        return $profile;
    }

    /** @return array<string, mixed> */
    private function profileData(MinorProfile $profile): array
    {
        return [
            'minor_profile_id' => $profile->id,
            'family_member_id' => $profile->family_member_id,
            'name' => $profile->familyMember->name,
            'member_code' => $profile->member_code,
            'status' => $profile->status->value,
            'direct_payment_enabled' => $profile->direct_payment_enabled,
            'wallet_spending_enabled' => $profile->wallet_spending_enabled,
        ];
    }

    private function requireMinorAccounts(): void
    {
        if (! config('byruhaa.minor_accounts.enabled', true)) {
            throw ValidationException::withMessages(['minor_profile_id' => 'Minor accounts are currently disabled.']);
        }
    }

    private function requireDeliveryConfiguration(): void
    {
        foreach (['webhook_url', 'webhook_bearer_token', 'webhook_signing_secret'] as $key) {
            if (blank(config('byruhaa.uchat.'.$key))) {
                throw ValidationException::withMessages(['verification' => 'Verification delivery is not configured.']);
            }
        }
    }
}
