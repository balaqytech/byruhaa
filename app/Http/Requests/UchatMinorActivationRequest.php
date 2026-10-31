<?php

namespace App\Http\Requests;

use App\Http\Requests\Integrations\Uchat\UchatRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UchatMinorActivationRequest extends UchatRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'uchat_phone' => $this->phoneRules(),
            'consent_accepted' => ['accepted'],
        ];
    }
}
