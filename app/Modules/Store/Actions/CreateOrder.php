<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\OrderPickupType;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Settings\StoreSettings;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    public function __construct(
        private ReserveInventory $reserveInventory,
        private QuoteCart $quoteCart,
        private StoreSettings $settings,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Cart $cart, array $data): Order
    {
        if (is_string($data['note'] ?? null)
            && count(preg_split('/\s+/u', trim((string) $data['note']), -1, PREG_SPLIT_NO_EMPTY) ?: []) > 50) {
            throw ValidationException::withMessages(['note' => 'The note may contain no more than 50 words.']);
        }

        return DB::transaction(function () use ($cart, $data): Order {
            $cart = Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();
            $cartCustomerId = $cart->getRawOriginal('customer_id');
            $orderCustomerId = $data['customer_id'] ?? null;

            if ($cartCustomerId !== null && (int) $cartCustomerId !== (int) $orderCustomerId) {
                throw ValidationException::withMessages(['cart' => 'This cart does not belong to the customer creating the order.']);
            }

            $existing = Order::query()->where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first();

            if ($existing instanceof Order) {
                if (($data['customer_id'] ?? null) !== $existing->customer_id || $existing->customer_phone !== $data['customer_phone']) {
                    throw ValidationException::withMessages(['idempotency_key' => 'This idempotency key belongs to another order.']);
                }

                return $existing->load(['items', 'statusHistory', 'inventoryReservation.reservation.items']);
            }

            if (! $this->settings->ordering_enabled) {
                throw ValidationException::withMessages(['ordering' => 'Store ordering is currently disabled.']);
            }

            $this->validatePickup($data);
            $quote = $this->quoteCart->execute($cart, true);

            $order = Order::query()->create([
                'idempotency_key' => $data['idempotency_key'],
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'note' => $data['note'] ?? null,
                'pickup_type' => $data['pickup_type'],
                'pickup_at' => $data['pickup_type'] === OrderPickupType::Scheduled->value ? $data['pickup_at'] : null,
                'subtotal_baisa' => $quote['subtotal_baisa'],
                'vat_baisa' => $quote['vat_baisa'],
                'total_baisa' => $quote['total_baisa'],
                'currency' => $quote['currency'],
            ]);

            $order->items()->createMany($quote['items']);
            $order->statusHistory()->create(['from_status' => null, 'to_status' => 'pending_payment']);

            if ($quote['reservation_quantities'] !== []) {
                $reservation = $this->reserveInventory->execute($quote['reservation_quantities']);
                $order->inventoryReservation()->create(['reservation_id' => $reservation->id]);
            }

            $cart->items()->delete();
            $cart->forceFill(['last_activity_at' => now()])->save();

            return $order->load(['items', 'statusHistory', 'inventoryReservation.reservation.items']);
        });
    }

    /** @param array<string, mixed> $data */
    private function validatePickup(array $data): void
    {
        $type = (string) ($data['pickup_type'] ?? '');

        if ($type === OrderPickupType::Immediate->value) {
            return;
        }

        if ($type !== OrderPickupType::Scheduled->value || ! isset($data['pickup_at'])) {
            throw ValidationException::withMessages(['pickup_at' => 'A scheduled pickup time is required.']);
        }

        try {
            $pickupAt = Carbon::parse($data['pickup_at']);
        } catch (InvalidFormatException) {
            throw ValidationException::withMessages(['pickup_at' => 'The scheduled pickup time is invalid.']);
        }

        if ($pickupAt->lt(now()->addHours(4))) {
            throw ValidationException::withMessages(['pickup_at' => 'Scheduled pickup must be at least four hours from now.']);
        }

        if (blank($this->settings->opening_time) || blank($this->settings->closing_time)) {
            throw ValidationException::withMessages(['pickup_at' => 'Store opening hours must be configured for scheduled pickup.']);
        }

        try {
            $opening = Carbon::parse($pickupAt->toDateString().' '.$this->settings->opening_time);
            $closing = Carbon::parse($pickupAt->toDateString().' '.$this->settings->closing_time);
        } catch (InvalidFormatException) {
            throw ValidationException::withMessages(['pickup_at' => 'Store opening hours are invalid.']);
        }

        if ($pickupAt->lt($opening) || $pickupAt->gt($closing)) {
            throw ValidationException::withMessages(['pickup_at' => 'Scheduled pickup must be within store opening hours.']);
        }
    }
}
