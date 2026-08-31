<?php

namespace App\Modules\Identity\Actions;

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
    /**
     * @param  array<string, mixed>  $data
     * @return array{profile: MinorProfile, code: string}
     */
    public function execute(Customer $guardian, array $data): array
    {
        if (! config('byruhaa.minor_accounts.enabled', true)) {
            throw ValidationException::withMessages(['minor_accounts' => 'Minor accounts are currently disabled.']);
        }

        return DB::transaction(function () use ($guardian, $data): array {
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
                'status' => MinorProfileStatus::PendingGuardianVerification,
            ]);

            $code = (string) random_int(100000, 999999);
            $profile->verifications()->create([
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes((int) config('byruhaa.minor_accounts.otp_expiry_minutes', 10)),
            ]);

            return compact('profile', 'code');
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
            $code = 'BRH-'.Str::upper(Str::random(8));
        } while (MinorProfile::query()->where('member_code', $code)->exists());

        return $code;
    }
}
