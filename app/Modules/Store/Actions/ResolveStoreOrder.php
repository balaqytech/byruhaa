<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Order;
use Illuminate\Validation\ValidationException;

class ResolveStoreOrder
{
    public function execute(string $paymentToken, ?int $customerId = null, bool $allowSignedLink = false): Order
    {
        $order = Order::query()->where('payment_token', $paymentToken)->firstOrFail();
        $ownerId = $order->getRawOriginal('customer_id');

        if ($ownerId !== null && $customerId !== null && (int) $ownerId !== $customerId) {
            throw ValidationException::withMessages(['order' => 'This order does not belong to the authenticated customer.']);
        }

        if ($ownerId !== null && $customerId === null && ! $allowSignedLink) {
            throw ValidationException::withMessages(['order' => 'Authentication or a signed payment link is required.']);
        }

        if ($ownerId === null && $customerId !== null) {
            throw ValidationException::withMessages(['order' => 'Guest orders must be accessed with their payment link.']);
        }

        return $order;
    }
}
