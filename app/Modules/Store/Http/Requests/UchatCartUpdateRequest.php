<?php

namespace App\Modules\Store\Http\Requests;

class UchatCartUpdateRequest extends UchatRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'uchat_phone' => $this->phoneRules(),
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
