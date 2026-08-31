<?php

namespace App\Modules\Store\Http\Requests;

use App\Modules\Identity\Contracts\CustomerIdentityResolver;
use Illuminate\Contracts\Validation\ValidationRule;

class UchatOrderRequest extends UchatRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $normalizer = app(CustomerIdentityResolver::class);
        $recipientPhone = filled($this->input('recipient_phone'))
            ? $normalizer->normalizePhone((string) $this->input('recipient_phone'))
            : null;
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
            'customer_email' => filled($this->input('customer_email')) ? strtolower(trim((string) $this->input('customer_email'))) : null,
            'recipient_phone' => $recipientPhone,
        ]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'uchat_phone' => $this->phoneRules(),
            'idempotency_key' => ['required', 'string', 'max:100'],
            'minor_profile_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'phone:INTERNATIONAL,OM', 'max:32'],
            'note' => ['nullable', 'string', 'max:5000'],
            'pickup_type' => ['required', 'in:immediate,scheduled'],
            'pickup_at' => ['nullable', 'date'],
        ];
    }
}
