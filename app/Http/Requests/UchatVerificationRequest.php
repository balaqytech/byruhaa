<?php

namespace App\Http\Requests;

use App\Modules\Store\Http\Requests\UchatRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UchatVerificationRequest extends UchatRequest
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
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'consent_accepted' => [$this->routeIs('api.v1.integrations.uchat.store.minor-profiles.verify') ? 'accepted' : 'nullable'],
        ];
    }
}
