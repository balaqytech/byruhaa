<?php

namespace App\Modules\Store\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OrderRefundFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (int) $this->route('order')?->customer_id === (int) $this->user('customer')?->getAuthIdentifier();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'operation_key' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:255'],
            'amount_omr' => ['nullable', 'numeric', 'min:0.001'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
