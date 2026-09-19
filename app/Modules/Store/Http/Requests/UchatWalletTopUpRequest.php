<?php

namespace App\Modules\Store\Http\Requests;

class UchatWalletTopUpRequest extends UchatRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'uchat_phone' => $this->phoneRules(),
            'idempotency_key' => ['required', 'string', 'max:100'],
            'minor_profile_id' => ['required', 'integer'],
            'amount_omr' => ['required', 'regex:/^\d+(\.\d{1,3})?$/', 'numeric', 'min:0.100', 'max:100000'],
        ];
    }
}
