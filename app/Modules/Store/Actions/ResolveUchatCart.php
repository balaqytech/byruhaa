<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveUchatCart
{
    public function execute(string $ownerKey, ?int $customerId = null, bool $create = true): Cart
    {
        return DB::transaction(function () use ($ownerKey, $customerId, $create): Cart {
            $cart = Cart::query()->where('uchat_owner_key', $ownerKey)->lockForUpdate()->first();

            if ($cart instanceof Cart) {
                if ($customerId !== null && $cart->getRawOriginal('customer_id') === null) {
                    $cart->forceFill(['customer_id' => $customerId])->save();
                }

                return $cart;
            }

            if (! $create) {
                throw ValidationException::withMessages(['cart' => 'Cart not found.']);
            }

            return Cart::query()->create([
                'customer_id' => $customerId,
                'uchat_owner_key' => $ownerKey,
            ]);
        });
    }
}
