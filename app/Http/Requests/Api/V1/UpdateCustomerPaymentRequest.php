<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PaymentState;
use App\Models\BookingInstallment;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerPaymentRequest extends FormRequest
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
            'booking_installment_id' => ['sometimes', 'required', 'integer', Rule::exists(BookingInstallment::class, 'id')],
            'provider' => ['sometimes', 'required', 'string', 'max:255'],
            'reference' => ['sometimes', 'required', 'string', 'max:255', Rule::unique(Payment::class)->ignore($this->route('payment'))],
            'amount_baisa' => ['sometimes', 'required', 'integer', 'min:1'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'state' => ['sometimes', 'required', Rule::enum(PaymentState::class)],
            'provider_session_id' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(Payment::class)->ignore($this->route('payment'))],
            'provider_payment_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'provider_invoice' => ['sometimes', 'nullable', 'string', 'max:255'],
            'provider_payment_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'checkout_url' => ['sometimes', 'nullable', 'string'],
            'request_payload' => ['sometimes', 'nullable', 'array'],
            'response_payload' => ['sometimes', 'nullable', 'array'],
            'verified_at' => ['sometimes', 'nullable', 'date'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
