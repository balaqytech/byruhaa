<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletWithdrawalFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $minorProfile = $this->route('minorProfile');

        return $minorProfile !== null
            && (int) $minorProfile->familyMember()->value('customer_id') === (int) $this->user('customer')?->getAuthIdentifier();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount_omr' => ['required', 'numeric', 'min:0.001'],
            'method' => ['required', Rule::in(['cash', 'bank'])],
            'bank_account' => ['required_if:method,bank', 'nullable', 'string', 'max:100'],
            'bank_name' => ['required_if:method,bank', 'nullable', 'string', 'max:255'],
            'operation_key' => ['required', 'uuid'],
        ];
    }
}
