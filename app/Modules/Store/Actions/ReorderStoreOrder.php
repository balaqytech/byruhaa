<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Validation\ValidationException;

class ReorderStoreOrder
{
    public function __construct(
        private ResolveCart $resolveCart,
        private AddCartItem $addCartItem,
    ) {}

    /**
     * @return array{cart: Cart|null, added: array<int, string>, unavailable: array<int, string>}
     */
    public function execute(Order $order, int $customerId): array
    {
        $ownedOrder = Order::query()
            ->whereKey($order->getKey())
            ->where('customer_id', $customerId)
            ->with('items')
            ->firstOrFail();

        if (! in_array($ownedOrder->status->getValue(), [
            OrderStatus::Confirmed->value,
            OrderStatus::Accepted->value,
            OrderStatus::Preparing->value,
            OrderStatus::ReadyForPickup->value,
            OrderStatus::Completed->value,
            OrderStatus::Refunded->value,
        ], true)) {
            throw ValidationException::withMessages(['order' => 'This order cannot be reordered.']);
        }

        $availableItems = [];
        $unavailable = [];

        foreach ($ownedOrder->items as $item) {
            $option = ProductOption::query()
                ->with([
                    'product.category',
                    'reservationItems' => fn ($query) => $query->whereHas('reservation', fn ($reservationQuery) => $reservationQuery
                        ->where('status', 'pending')
                        ->where('expires_at', '>', now())),
                ])
                ->find($item->product_option_id);

            if (! $option instanceof ProductOption || ! $this->canAdd($option, (int) $item->quantity)) {
                $unavailable[] = $item->product_name.' - '.$item->option_name;

                continue;
            }

            $availableItems[] = [$option, (int) $item->quantity, $item->note, $item->product_name.' - '.$item->option_name];
        }

        if ($availableItems === []) {
            return ['cart' => null, 'added' => [], 'unavailable' => $unavailable];
        }

        $cart = Cart::query()->where('customer_id', $customerId)->latest('id')->first()
            ?? $this->resolveCart->execute(null, $customerId);
        $added = [];

        foreach ($availableItems as [$option, $quantity, $note, $label]) {
            try {
                $this->addCartItem->execute($cart, $option, $quantity, $note, true);
                $added[] = $label;
            } catch (ValidationException) {
                $unavailable[] = $label;
            }
        }

        return ['cart' => $cart->refresh(), 'added' => $added, 'unavailable' => array_values(array_unique($unavailable))];
    }

    private function canAdd(ProductOption $option, int $quantity): bool
    {
        try {
            AddCartItem::ensurePurchasable($option);
        } catch (ValidationException) {
            return false;
        }

        if ($option->currency !== 'OMR' || $quantity < 1 || $quantity > 99) {
            return false;
        }

        return ! $option->tracks_inventory || ($option->availableQuantity() ?? 0) >= $quantity;
    }
}
