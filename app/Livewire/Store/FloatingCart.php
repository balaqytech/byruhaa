<?php

namespace App\Livewire\Store;

use App\Modules\Store\Actions\QuoteCart;
use App\Modules\Store\Actions\RemoveCartItem;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Actions\UpdateCartItem;
use App\Modules\Store\Models\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class FloatingCart extends Component
{
    protected ResolveCart $resolveCart;

    protected UpdateCartItem $updateCartItem;

    protected RemoveCartItem $removeCartItem;

    protected QuoteCart $quoteCart;

    public int $itemCount = 0;

    public bool $cartOpen = false;

    public ?string $cartError = null;

    public function boot(ResolveCart $resolveCart, UpdateCartItem $updateCartItem, RemoveCartItem $removeCartItem, QuoteCart $quoteCart): void
    {
        $this->resolveCart = $resolveCart;
        $this->updateCartItem = $updateCartItem;
        $this->removeCartItem = $removeCartItem;
        $this->quoteCart = $quoteCart;
    }

    public function mount(): void
    {
        $this->refreshCart();
    }

    #[On('store-cart-updated')]
    public function refreshCart(): void
    {
        $token = session('store_cart_token');

        if (blank($token) && $this->customerId() === null) {
            $this->itemCount = 0;

            return;
        }

        try {
            $cart = $this->resolveCart->execute($token, $this->customerId(), false);
            $this->itemCount = (int) $cart->items()->sum('quantity');
            if ($this->itemCount === 0) {
                $this->cartOpen = false;
            }
        } catch (ValidationException) {
            $this->itemCount = 0;
            $this->cartOpen = false;
        }
    }

    public function openCart(): void
    {
        $this->refreshCart();
        $this->cartOpen = $this->itemCount > 0;
    }

    public function closeCart(): void
    {
        $this->cartOpen = false;
    }

    public function updateItem(int $itemId, int $quantity): void
    {
        try {
            $cart = $this->resolveExistingCart();
            $item = $cart->items()->whereKey($itemId)->firstOrFail();
            $this->updateCartItem->execute($cart, $item, $quantity, $item->note);
            $this->resetValidation();
            $this->refreshCart();
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function updateNote(int $itemId, string $note): void
    {
        try {
            $cart = $this->resolveExistingCart();
            $item = $cart->items()->whereKey($itemId)->firstOrFail();
            $this->updateCartItem->execute($cart, $item, (int) $item->quantity, $note);
            $this->resetValidation();
            $this->refreshCart();
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function removeItem(int $itemId): void
    {
        try {
            $cart = $this->resolveExistingCart();
            $item = $cart->items()->whereKey($itemId)->firstOrFail();
            $this->removeCartItem->execute($cart, $item);
            $this->resetValidation();
            $this->refreshCart();
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function render(): View
    {
        $cart = $this->loadCart();
        $quote = null;

        if ($cart?->items->isNotEmpty()) {
            try {
                $quote = $this->quoteCart->execute($cart);
                $this->cartError = null;
            } catch (ValidationException $exception) {
                $this->cartError = collect($exception->errors())->flatten()->first();
            }
        }

        return view('livewire.store.floating-cart', compact('cart', 'quote'));
    }

    private function loadCart(): ?Cart
    {
        if (blank(session('store_cart_token')) && $this->customerId() === null) {
            return null;
        }

        try {
            return $this->resolveCart->execute(session('store_cart_token'), $this->customerId(), false)
                ->load('items.productOption.product.category');
        } catch (ValidationException) {
            return null;
        }
    }

    private function resolveExistingCart(): Cart
    {
        return $this->resolveCart->execute(session('store_cart_token'), $this->customerId(), false)
            ->load('items.productOption.product.category');
    }

    private function showValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, is_array($messages) ? (string) ($messages[0] ?? 'تعذّر تحديث السلة.') : (string) $messages);
        }
    }

    private function customerId(): ?int
    {
        $identifier = auth('customer')->user()?->getAuthIdentifier();

        return is_numeric($identifier) ? (int) $identifier : null;
    }
}
