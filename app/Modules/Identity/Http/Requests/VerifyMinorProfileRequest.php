<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Foundation\Http\FormRequest;

class VerifyMinorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('minorProfile');
        $guardian = $this->user('customer');

        return $guardian !== null
            && $profile instanceof MinorProfile
            && (int) $profile->familyMember()->value('customer_id') === (int) $guardian->getAuthIdentifier();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }
}
