<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\OrderPickupType;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    public function __construct(private ReserveInventory $reserveInventory, private StoreSettings $settings) {}

    /** @param array<string, mixed> $data */
    public function execute(Cart $cart, array $data): Order
    {
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

            $items = $cart->items()->with('productOption.product.category')->orderBy('product_option_id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'The cart is empty.']);
            }

            $this->validatePickup($data);
            $subtotal = 0;
            $vat = 0;
            $snapshots = [];
            $reservationQuantities = [];

            foreach ($items as $item) {
                $option = ProductOption::query()
                    ->with('product.category')
                    ->whereKey($item->product_option_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                AddCartItem::ensurePurchasable($option);

                if ($option->currency !== 'OMR') {
                    throw ValidationException::withMessages(['currency' => 'Only OMR products can be ordered.']);
                }
                $lineSubtotal = $option->price_baisa * $item->quantity;
                $lineVat = intdiv($lineSubtotal * $this->settings->vat_rate_percentage, 100);
                $subtotal += $lineSubtotal;
                $vat += $lineVat;
                $snapshots[] = [
                    'product_option_id' => $option->id,
                    'product_name' => $option->product->name,
                    'option_name' => $option->name,
                    'sku' => $option->sku,
                    'currency' => $option->currency,
                    'unit_price_baisa' => $option->price_baisa,
                    'quantity' => $item->quantity,
                    'vat_baisa' => $lineVat,
                    'line_subtotal_baisa' => $lineSubtotal,
                    'line_total_baisa' => $lineSubtotal + $lineVat,
                    'note' => $item->note,
                ];

                if ($option->tracks_inventory) {
                    $reservationQuantities[$option->id] = ($reservationQuantities[$option->id] ?? 0) + $item->quantity;
                }
            }

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
                'subtotal_baisa' => $subtotal,
                'vat_baisa' => $vat,
                'total_baisa' => $subtotal + $vat,
                'currency' => 'OMR',
            ]);

            $order->items()->createMany($snapshots);
            $order->statusHistory()->create(['from_status' => null, 'to_status' => 'pending_payment']);

            if ($reservationQuantities !== []) {
                $reservation = $this->reserveInventory->execute($reservationQuantities);
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
