<?php

namespace App\Modules\Store\Http\Requests;

class UchatCartItemRequest extends UchatRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'uchat_phone' => $this->phoneRules(),
            'minor_profile_id' => ['nullable', 'integer'],
            'sku' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
