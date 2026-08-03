<?php

namespace App\Http\Requests\Api\V1;

use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    private const PROTECTED_FIELDS = [
        'password',
        'password_confirmation',
        'additional_info',
        'remember_token',
        'email_verified_at',
        'status',
        'role',
        'balance',
        'balance_baisa',
        'affiliate_id',
        'affiliate_code',
        'affiliate_data',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(Customer::class)->ignore($customer)],
            'phone_number' => ['required', 'string', 'phone:INTERNATIONAL,OM', Rule::unique(Customer::class)->ignore($customer)],
            'civil_id' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'wilaya' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
        ];

        foreach (self::PROTECTED_FIELDS as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
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
