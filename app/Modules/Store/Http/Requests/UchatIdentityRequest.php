<?php

namespace App\Modules\Store\Http\Requests;

class UchatIdentityRequest extends UchatRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'uchat_phone' => $this->phoneRules(),
            'minor_profile_id' => ['nullable', 'integer'],
        ];
    }
}
