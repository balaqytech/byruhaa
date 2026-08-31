<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Cart;
use Illuminate\Validation\ValidationException;

class ResolveCart
{
    public function execute(?string $token, ?int $customerId, bool $create = true, ?int $minorProfileId = null): Cart
    {
        $cart = null;

        if (filled($token)) {
            $cart = Cart::query()->where('token', $token)->first();
            $ownerId = (string) ($cart?->getRawOriginal('customer_id') ?? '');

            if ($cart instanceof Cart && filled($ownerId)) {
                if ($customerId === null) {
                    throw ValidationException::withMessages(['cart' => 'Authentication is required to access this customer cart.']);
                }

                if ((int) $ownerId !== $customerId) {
                    throw ValidationException::withMessages(['cart' => 'This cart does not belong to the authenticated customer.']);
                }
            } elseif ($cart instanceof Cart && $customerId !== null) {
                $cart->forceFill(['customer_id' => $customerId])->save();
            }
        } else {
            $cart = $customerId !== null
                ? Cart::query()->where('customer_id', $customerId)
                    ->when($minorProfileId !== null, fn ($query) => $query->where('minor_profile_id', $minorProfileId))
                    ->when($minorProfileId === null, fn ($query) => $query->whereNull('minor_profile_id'))
                    ->latest('id')
                    ->first()
                : null;
        }

        if ($cart instanceof Cart) {
            if ($customerId !== null && (int) $cart->getRawOriginal('customer_id') !== $customerId) {
                throw ValidationException::withMessages(['cart' => 'This cart does not belong to the authenticated customer.']);
            }

            $cartMinorProfileId = $cart->getRawOriginal('minor_profile_id');

            if ($cartMinorProfileId !== null && ($minorProfileId === null || (int) $cartMinorProfileId !== $minorProfileId)) {
                throw ValidationException::withMessages(['cart' => 'This cart belongs to another minor profile.']);
            }

            if ($cartMinorProfileId === null && $minorProfileId !== null) {
                throw ValidationException::withMessages(['cart' => 'This cart belongs to the guardian account.']);
            }

            return $cart;
        }

        if (! $create) {
            throw ValidationException::withMessages(['cart' => 'Cart not found.']);
        }

        return Cart::query()->create(['customer_id' => $customerId, 'minor_profile_id' => $minorProfileId]);
    }
}
