<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveUchatCart
{
    public function execute(string $ownerKey, ?int $customerId = null, bool $create = true, ?int $minorProfileId = null): Cart
    {
        $scopedOwnerKey = $minorProfileId === null
            ? $ownerKey
            : hash('sha256', $ownerKey.'|minor-profile|'.$minorProfileId);

        return DB::transaction(function () use ($scopedOwnerKey, $customerId, $create, $minorProfileId): Cart {
            $cart = Cart::query()
                ->where('uchat_owner_key', $scopedOwnerKey)
                ->lockForUpdate()
                ->first();

            if ($cart instanceof Cart) {
                if ($customerId !== null && $cart->getRawOriginal('customer_id') !== null && (int) $cart->getRawOriginal('customer_id') !== $customerId) {
                    throw ValidationException::withMessages(['cart' => 'This cart does not belong to the authenticated customer.']);
                }

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
                'minor_profile_id' => $minorProfileId,
                'uchat_owner_key' => $scopedOwnerKey,
            ]);
        });
    }
}
