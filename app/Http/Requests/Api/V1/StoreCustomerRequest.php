<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Customer;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(Customer::class)],
            'phone_number' => ['required', 'string', 'phone:OM', Rule::unique(Customer::class)],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => blank($this->input('email')) ? null : $this->input('email'),
            'phone_number' => app(PhoneNumberNormalizer::class)->normalize($this->input('phone_number')),
        ]);
    }
}
