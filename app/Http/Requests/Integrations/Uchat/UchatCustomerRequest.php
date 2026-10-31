<?php

namespace App\Http\Requests\Integrations\Uchat;

use App\Modules\Identity\Models\Customer;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UchatCustomerRequest extends UchatRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique(Customer::class)->ignore($creating ? null : $this->customerId())],
            'civil_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'wilaya' => ['sometimes', 'nullable', 'string', 'max:255'],
            'area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => $creating ? ['required', 'string', Password::default(), 'confirmed'] : ['prohibited'],
            'password_confirmation' => $creating ? ['required', 'string'] : ['prohibited'],
            'phone_verified_at' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'additional_info' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if ($this->has('email')) {
            $this->merge(['email' => filled($this->input('email')) ? mb_strtolower(trim((string) $this->input('email'))) : null]);
        }
    }
}
