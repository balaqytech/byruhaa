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
            'phone_number' => ['required', 'string', 'phone:INTERNATIONAL,OM', Rule::unique(Customer::class)],
            'civil_id' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'wilaya' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'additional_info' => ['nullable', 'array'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => blank($this->input('email')) ? null : $this->input('email'),
            'phone_number' => app(PhoneNumberNormalizer::class)->normalize($this->input('phone_number')),
            'civil_id' => blank($this->input('civil_id')) ? null : $this->input('civil_id'),
            'address' => blank($this->input('address')) ? null : $this->input('address'),
            'wilaya' => blank($this->input('wilaya')) ? null : $this->input('wilaya'),
            'area' => blank($this->input('area')) ? null : $this->input('area'),
        ]);
    }
}
