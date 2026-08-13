<?php

namespace App\Http\Requests\Store;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:5000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (count(preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: []) > 50) {
                    $fail('The note may contain no more than 50 words.');
                }
            }],
            'pickup_type' => ['required', 'in:immediate,scheduled'],
            'pickup_at' => ['nullable', 'date'],
        ];
    }
}
