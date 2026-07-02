<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventStatus;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerBookingRequest extends FormRequest
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
        $customer = $this->route('customer');
        $customerId = $customer instanceof Customer ? $customer->id : null;

        return [
            'event_id' => [
                'required',
                'integer',
                Rule::exists(Event::class, 'id')->where('status', EventStatus::Published->value),
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
