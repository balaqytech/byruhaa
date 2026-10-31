<?php

namespace App\Modules\Identity\Webhooks;

use App\Modules\Identity\Models\Customer;
use Carbon\CarbonInterface;

class CustomerWebhookPayload
{
    /**
     * @return array<string, mixed>
     */
    public function customerPayload(string $eventName, Customer $customer, CarbonInterface $occurredAt): array
    {
        return [
            'event' => $eventName,
            'customer_phone' => $customer->phone_number,
            'occurred_at' => $occurredAt->toJSON(),
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone_number,
                    'email' => $customer->email,
                    'civil_id' => $customer->civil_id,
                    'wilaya' => $customer->wilaya,
                    'area' => $customer->area,
                    'address' => $customer->address,
                    'profile_complete' => $customer->hasCompleteProfile(),
                    'missing_required_profile_fields' => $customer->missingRequiredProfileFields(),
                    'customer_panel_url' => route('customer.dashboard'),
                    'created_at' => $customer->created_at?->toJSON(),
                ],
            ],
        ];
    }
}
