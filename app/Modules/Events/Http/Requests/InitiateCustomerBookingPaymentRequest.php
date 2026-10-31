<?php

namespace App\Modules\Events\Http\Requests;

use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\EventPaymentPlan;
use Illuminate\Validation\Rule;

class InitiateCustomerBookingPaymentRequest extends UchatRequest
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
