<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveCartItem
{
    public function execute(Cart $cart, CartItem $item): void
    {
        if ($item->cart_id !== $cart->id) {
            throw ValidationException::withMessages(['cart' => 'This item does not belong to the cart.']);
        }

        DB::transaction(function () use ($cart, $item): void {
            CartItem::query()->whereKey($item->id)->where('cart_id', $cart->id)->lockForUpdate()->firstOrFail()->delete();
            Cart::query()->whereKey($cart->id)->update(['last_activity_at' => now(), 'updated_at' => now()]);
        });
    }
}
