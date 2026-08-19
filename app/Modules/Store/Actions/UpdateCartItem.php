<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCartItem
{
    public function execute(Cart $cart, CartItem $item, int $quantity, ?string $note = null, bool $checkInventory = false): CartItem
    {
        if ($item->cart_id !== $cart->id) {
            throw ValidationException::withMessages(['cart' => 'This item does not belong to the cart.']);
        }

        if ($quantity < 1 || $quantity > 99) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be between 1 and 99.']);
        }

        if ($note !== null && count(preg_split('/\s+/u', trim($note), -1, PREG_SPLIT_NO_EMPTY) ?: []) > 50) {
            throw ValidationException::withMessages(['note' => 'The note may contain no more than 50 words.']);
        }

        return DB::transaction(function () use ($cart, $item, $quantity, $note, $checkInventory): CartItem {
            $cart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $item = CartItem::query()->whereKey($item->id)->where('cart_id', $cart->id)->lockForUpdate()->firstOrFail();
            $option = $item->productOption()->with('product.category')->firstOrFail();
            AddCartItem::ensurePurchasable($option);

            if ($checkInventory && $option->tracks_inventory && ($option->availableQuantity() ?? 0) < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'The requested quantity is not currently available.']);
            }
            $item->forceFill(['quantity' => $quantity, 'note' => $note])->save();
            $cart->forceFill(['last_activity_at' => now()])->save();

            return $item->load(['productOption.product.category']);
        });
    }
}
