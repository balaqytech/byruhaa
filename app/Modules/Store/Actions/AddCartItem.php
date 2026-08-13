<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\CartItem;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddCartItem
{
    public function execute(Cart $cart, ProductOption $productOption, int $quantity, ?string $note = null, bool $checkInventory = false): CartItem
    {
        if ($quantity < 1 || $quantity > 99) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be between 1 and 99.']);
        }

        $this->validateNote($note);

        return DB::transaction(function () use ($cart, $productOption, $quantity, $note, $checkInventory): CartItem {
            $cart = Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();
            $option = ProductOption::query()->with(['product.category'])->whereKey($productOption->getKey())->lockForUpdate()->firstOrFail();

            $this->ensurePurchasable($option);
            $item = CartItem::query()->where('cart_id', $cart->id)->where('product_option_id', $option->id)->lockForUpdate()->first();
            $nextQuantity = ($item ? $item->quantity : 0) + $quantity;

            if ($nextQuantity > 99) {
                throw ValidationException::withMessages(['quantity' => 'Quantity must be between 1 and 99.']);
            }

            if ($checkInventory && $option->tracks_inventory && ($option->availableQuantity() ?? 0) < $nextQuantity) {
                throw ValidationException::withMessages(['quantity' => 'The requested quantity is not currently available.']);
            }

            $item ??= new CartItem(['cart_id' => $cart->id, 'product_option_id' => $option->id]);
            $item->forceFill(['quantity' => $nextQuantity, 'note' => $note ?? $item->note])->save();
            $cart->forceFill(['last_activity_at' => now()])->save();

            return $item->load(['productOption.product.category']);
        });
    }

    public static function ensurePurchasable(ProductOption $option): void
    {
        if (! $option->is_available
            || $option->price_baisa <= 0
            || $option->product?->status->value !== 'active'
            || $option->product->category?->is_active !== true) {
            throw ValidationException::withMessages(['product_option_id' => 'This product is not available for purchase.']);
        }
    }

    private function validateNote(?string $note): void
    {
        if ($note !== null && count(preg_split('/\s+/u', trim($note), -1, PREG_SPLIT_NO_EMPTY) ?: []) > 50) {
            throw ValidationException::withMessages(['note' => 'The note may contain no more than 50 words.']);
        }
    }
}
