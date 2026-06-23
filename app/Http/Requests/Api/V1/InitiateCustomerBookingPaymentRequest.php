<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BookingInstallment;
use App\Models\EventPaymentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateCustomerBookingPaymentRequest extends FormRequest
{
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
        return [
            'payment_plan_id' => ['sometimes', 'nullable', 'integer', Rule::exists(EventPaymentPlan::class, 'id')],
            'booking_installment_id' => ['sometimes', 'nullable', 'integer', Rule::exists(BookingInstallment::class, 'id')],
        ];
    }
}
