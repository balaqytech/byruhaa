<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PaymentState;
use App\Models\BookingInstallment;
use App\Modules\Finance\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerPaymentRequest extends FormRequest
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
            'booking_installment_id' => ['required', 'integer', Rule::exists(BookingInstallment::class, 'id')],
            'provider' => ['sometimes', 'required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255', Rule::unique(Payment::class)],
            'amount_baisa' => ['required', 'integer', 'min:1'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'state' => ['sometimes', 'required', Rule::enum(PaymentState::class)],
            'provider_session_id' => ['nullable', 'string', 'max:255', Rule::unique(Payment::class)],
            'provider_payment_id' => ['nullable', 'string', 'max:255'],
            'provider_invoice' => ['nullable', 'string', 'max:255'],
            'provider_payment_status' => ['nullable', 'string', 'max:255'],
            'checkout_url' => ['nullable', 'string'],
            'request_payload' => ['nullable', 'array'],
            'response_payload' => ['nullable', 'array'],
            'verified_at' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
