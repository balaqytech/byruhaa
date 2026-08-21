<?php

namespace App\Livewire\Store;

use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\BrowseCatalog;
use App\Modules\Store\Actions\QuoteCart;
use App\Modules\Store\Actions\RemoveCartItem;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Actions\UpdateCartItem;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CoffeeStore extends Component
{
    private const CHECKOUT_IDEMPOTENCY_KEYS_SESSION = 'store_checkout_idempotency_keys';

    private const LEGACY_CHECKOUT_IDEMPOTENCY_KEY_SESSION = 'store_checkout_idempotency_key';

    protected BrowseCatalog $browseCatalog;

    protected QuoteCart $quoteCart;

    protected ResolveCart $resolveCart;

    protected StoreSettings $settings;

    public ?int $categoryId = null;

    public bool $featuredOnly = false;

    /** @var array<int, int|string> */
    public array $selectedOptions = [];

    public ?int $selectedOptionId = null;

    public ?string $feedback = null;

    public ?string $cartError = null;

    public ?string $cartToken = null;

    public function boot(BrowseCatalog $browseCatalog, QuoteCart $quoteCart, ResolveCart $resolveCart, StoreSettings $settings): void
    {
        $this->browseCatalog = $browseCatalog;
        $this->quoteCart = $quoteCart;
        $this->resolveCart = $resolveCart;
        $this->settings = $settings;
    }

    public function mount(): void
    {
        session()->forget(self::LEGACY_CHECKOUT_IDEMPOTENCY_KEY_SESSION);
        $this->cartToken = session('store_cart_token');
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->featuredOnly = false;
        $this->selectedOptionId = null;
    }

    public function selectFeatured(): void
    {
        $this->categoryId = null;
        $this->featuredOnly = true;
        $this->selectedOptionId = null;
    }

    public function addToCart(int $optionId, AddCartItem $addCartItem, ResolveCart $resolveCart): void
    {
        try {
            $cart = $resolveCart->execute($this->cartToken, $this->customerId(), true);
            $option = ProductOption::query()->with('product.category')->findOrFail($optionId);
            $addCartItem->execute($cart, $option, 1);
            $this->rememberCart($cart);
            $this->resetCheckoutAttempt();
            $this->feedback = 'أضيف المنتج إلى السلة.';
            $this->cartError = null;
            $this->dispatch('store-cart-updated');
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function updateCartItem(int $itemId, int $quantity, UpdateCartItem $updateCartItem): void
    {
        try {
            $cart = $this->resolveExistingCart();
            $item = $cart->items()->whereKey($itemId)->firstOrFail();
            $updateCartItem->execute($cart, $item, $quantity, $item->note);
            $this->resetCheckoutAttempt();
            $this->feedback = 'تم تحديث السلة.';
            $this->dispatch('store-cart-updated');
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function updateCartItemNote(int $itemId, string $note, UpdateCartItem $updateCartItem): void
    {
        try {
            $cart = $this->resolveExistingCart();
            $item = $cart->items()->whereKey($itemId)->firstOrFail();
            $updateCartItem->execute($cart, $item, (int) $item->quantity, $note);
            $this->resetCheckoutAttempt();
            $this->feedback = 'تم حفظ الملاحظة.';
            $this->dispatch('store-cart-updated');
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function removeFromCart(int $itemId, RemoveCartItem $removeCartItem): void
    {
        try {
            $cart = $this->resolveExistingCart();
            $item = $cart->items()->whereKey($itemId)->firstOrFail();
            $removeCartItem->execute($cart, $item);
            $this->resetCheckoutAttempt();
            $this->feedback = 'أزيل المنتج من السلة.';
            $this->dispatch('store-cart-updated');
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function openCheckout(): void
    {
        try {
            $this->quoteCart->execute($this->resolveExistingCart());
            $this->feedback = null;
            $this->idempotencyKey();
            $this->redirect(route('store.checkout'), navigate: true);
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function render(): View
    {
        $catalog = $this->browseCatalog->execute();
        $this->initializeSelectedOptions($catalog);

        $featuredCatalog = $catalog
            ->map(function (Category $category): Category {
                $featuredCategory = clone $category;
                $featuredCategory->setRelation(
                    'products',
                    $featuredCategory->products
                        ->filter(fn (Product $product): bool => $product->is_featured)
                        ->sortBy('featured_sort_order')
                        ->values(),
                );

                return $featuredCategory;
            })
            ->filter(fn (Category $category): bool => $category->products->isNotEmpty())
            ->values();

        if ($this->featuredOnly && $featuredCatalog->isEmpty()) {
            $this->featuredOnly = false;
        }

        $displayCatalog = $this->featuredOnly ? $featuredCatalog : $catalog;
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

        return view('livewire.store.coffee-store', [
            'catalog' => $catalog,
            'displayCatalog' => $displayCatalog,
            'hasFeaturedProducts' => $featuredCatalog->isNotEmpty(),
            'cart' => $cart,
            'quote' => $quote,
            'orderingEnabled' => $this->settings->ordering_enabled,
        ]);
    }

    /** @param Collection<int, Category> $catalog */
    private function initializeSelectedOptions(Collection $catalog): void
    {
        foreach ($catalog as $category) {
            foreach ($category->products as $product) {
                if ($product->options->count() < 2) {
                    continue;
                }

                $selectedOption = $product->options->firstWhere(
                    'id',
                    (int) ($this->selectedOptions[$product->getKey()] ?? 0),
                )
                    ?? $product->options->firstWhere('is_default', true)
                    ?? $product->options->first();

                if ($selectedOption !== null) {
                    $this->selectedOptions[$product->getKey()] = (int) $selectedOption->getKey();
                }
            }
        }
    }

    private function loadCart(): ?Cart
    {
        if (blank($this->cartToken) && $this->customerId() === null) {
            return null;
        }

        try {
            return $this->resolveCart->execute($this->cartToken, $this->customerId(), false)
                ->load('items.productOption.product.category');
        } catch (ValidationException) {
            return null;
        }
    }

    private function resolveExistingCart(): Cart
    {
        return $this->resolveCart->execute($this->cartToken, $this->customerId(), false)
            ->load('items.productOption.product.category');
    }

    private function rememberCart(Cart $cart): void
    {
        $this->cartToken = (string) $cart->token;
        session()->put('store_cart_token', $this->cartToken);
    }

    private function customerId(): ?int
    {
        $identifier = auth('customer')->user()?->getAuthIdentifier();

        return is_numeric($identifier) ? (int) $identifier : null;
    }

    private function idempotencyKey(): string
    {
        $sessionKey = $this->checkoutIdempotencySessionKey();
        $key = session($sessionKey);

        if (! is_string($key) || blank($key)) {
            $key = (string) Str::uuid();
            session()->put($sessionKey, $key);
        }

        return $key;
    }

    private function resetCheckoutAttempt(): void
    {
        if (filled($this->cartToken)) {
            session()->forget($this->checkoutIdempotencySessionKey());
        }
    }

    private function checkoutIdempotencySessionKey(): string
    {
        return self::CHECKOUT_IDEMPOTENCY_KEYS_SESSION.'.'.hash('sha256', (string) $this->cartToken);
    }

    private function showValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, is_array($messages) ? (string) ($messages[0] ?? 'تعذر تنفيذ الطلب.') : (string) $messages);
        }
    }
}
