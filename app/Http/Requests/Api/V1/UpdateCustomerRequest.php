<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Customer;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateCustomerRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique(Customer::class)->ignore($this->route('customer'))],
            'phone_number' => ['required', 'string', 'phone:OM', Rule::unique(Customer::class)->ignore($this->route('customer'))],
            'password' => ['sometimes', 'required', 'string', Password::default(), 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('email')) {
            $data['email'] = blank($this->input('email')) ? null : $this->input('email');
        }

        if ($this->has('phone_number')) {
            $data['phone_number'] = app(PhoneNumberNormalizer::class)->normalize($this->input('phone_number'));
        }

        $this->merge($data);
    }
}
