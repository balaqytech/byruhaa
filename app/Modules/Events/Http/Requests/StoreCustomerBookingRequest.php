<?php

namespace App\Modules\Events\Http\Requests;

use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Events\Models\Event;
use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Validation\Rule;

class StoreCustomerBookingRequest extends UchatRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $customer = $this->customer();
        $customerId = $customer->id;

        return [
            'idempotency_key' => ['required', 'string', 'max:100'],
            'event_id' => [
                'required',
                'integer',
                Rule::exists(Event::class, 'id'),
            ],
            'family_member_ids' => ['required', 'array', 'min:1'],
            'family_member_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(FamilyMember::class, 'id')->where('customer_id', $customerId),
            ],
            'coupon_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
