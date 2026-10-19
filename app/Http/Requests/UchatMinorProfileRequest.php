<?php

namespace App\Http\Requests;

use App\Modules\Store\Http\Requests\UchatRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UchatMinorProfileRequest extends UchatRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uchat_phone' => $this->phoneRules(),
            'family_member_id' => ['required', 'integer'],
            'consent_accepted' => ['accepted'],
            'notifications_consent_accepted' => ['accepted'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2048,max_height=2048', 'prohibits:avatar_base64'],
            'avatar_base64' => ['nullable', 'string', 'max:2800000', 'prohibits:avatar'],
        ];
    }
}
