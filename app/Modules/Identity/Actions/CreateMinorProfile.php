<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateMinorProfile
{
    public function __construct(
        private IssueMinorProfileActivation $issueActivation,
        private RecordMinorNotificationConsent $recordNotificationConsent,
        private WalletService $wallets,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{profile: MinorProfile, activation_token: string}
     */
    public function execute(Customer $guardian, array $data, ?string $ipAddress = null, bool $browserNotificationsConsent = false): array
    {
        if (! config('byruhaa.minor_accounts.enabled', true)) {
            throw ValidationException::withMessages(['minor_accounts' => 'Minor accounts are currently disabled.']);
        }

        return DB::transaction(function () use ($guardian, $data, $ipAddress, $browserNotificationsConsent): array {
            $familyMember = $this->resolveFamilyMember($guardian, $data);

            if ($familyMember->minorProfile()->exists()) {
                throw ValidationException::withMessages([
                    'family_member_id' => 'This family member already has a minor account.',
                ]);
            }

            if ($familyMember->birth_date->isFuture() || $familyMember->ageAt(now()) >= 18) {
                throw ValidationException::withMessages([
                    'birth_date' => 'A minor account can only be created for someone under 18.',
                ]);
            }

            $profile = MinorProfile::query()->create([
                'family_member_id' => $familyMember->id,
                'member_code' => $this->uniqueMemberCode(),
                'password' => Hash::make(Str::random(48)),
                'status' => MinorProfileStatus::PendingChildActivation,
                'wallet_spending_enabled' => true,
            ]);

            if ($browserNotificationsConsent) {
                $this->recordNotificationConsent->execute($profile, $ipAddress);
            }

            $activationToken = $this->issueActivation->execute($profile, $guardian->id, $ipAddress);
            $profile->consents()->create([
                'purpose' => 'wallet_spending',
                'policy_version' => (string) config('byruhaa.wallets.consent_policy_version', 'wallet-spending-v1'),
                'policy_hash' => hash('sha256', (string) config('byruhaa.wallets.consent_policy_text', 'guardian-consent-wallet-spending')),
                'accepted_at' => now(),
                'accepted_ip' => $ipAddress,
            ]);
            if (config('byruhaa.wallets.enabled', false)) {
                $this->wallets->walletForMinorProfile($profile->id);
            }

            return ['profile' => $profile->refresh(), 'activation_token' => $activationToken];
        });
    }

    /** @param array<string, mixed> $data */
    private function resolveFamilyMember(Customer $guardian, array $data): FamilyMember
    {
        if (filled($data['family_member_id'] ?? null)) {
            $familyMember = $guardian->familyMembers()
                ->whereKey((int) $data['family_member_id'])
                ->lockForUpdate()
                ->first();

            if (! $familyMember instanceof FamilyMember) {
                throw ValidationException::withMessages(['family_member_id' => 'The selected family member was not found.']);
            }

            return $familyMember;
        }

        return $guardian->familyMembers()->create([
            'name' => (string) $data['name'],
            'birth_date' => Carbon::parse((string) $data['birth_date'])->toDateString(),
            'school_name' => $data['school_name'] ?? null,
            'grade' => $data['grade'] ?? null,
            'relationship_to_customer' => $data['relationship_to_customer'] ?? null,
        ]);
    }

    private function uniqueMemberCode(): string
    {
        do {
            $code = chr(random_int(65, 90)).chr(random_int(65, 90)).sprintf('%03d', random_int(0, 999));
        } while (MinorProfile::query()->where('member_code', $code)->exists());

        return $code;
    }
}
