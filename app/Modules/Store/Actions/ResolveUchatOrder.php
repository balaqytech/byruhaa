<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Order;
use Illuminate\Validation\ValidationException;

class ResolveUchatOrder
{
    public function execute(string $reference, string $phone, ?int $minorProfileId = null): Order
    {
        $order = Order::query()
            ->with(['items', 'statusHistory'])
            ->where('reference', $reference)
            ->where('customer_phone', $phone)
            ->when($minorProfileId !== null, fn ($query) => $query->where('minor_profile_id', $minorProfileId))
            ->first();

        if (! $order instanceof Order) {
            throw ValidationException::withMessages(['order' => 'The order was not found for this phone number.']);
        }

        return $order;
    }
}
