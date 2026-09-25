<?php

namespace App\Livewire\Store;

use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\BrowseCatalog;
use App\Modules\Store\Actions\QuoteCart;
use App\Modules\Store\Actions\RemoveCartItem;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Actions\UpdateCartItem;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Services\PricingContextResolver;
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

    protected PricingContextResolver $pricingContexts;

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

    public function boot(
        BrowseCatalog $browseCatalog,
        QuoteCart $quoteCart,
        ResolveCart $resolveCart,
        StoreSettings $settings,
        PricingContextResolver $pricingContexts,
    ): void {
        $this->browseCatalog = $browseCatalog;
        $this->quoteCart = $quoteCart;
        $this->resolveCart = $resolveCart;
        $this->settings = $settings;
        $this->pricingContexts = $pricingContexts;
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
            $cart = $resolveCart->execute($this->cartToken, $this->customerId(), true, $this->minorProfileId());
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

        if (! $this->featuredOnly && ! $catalog->contains('id', $this->categoryId)) {
            $this->categoryId = $catalog->first()?->getKey();
        }

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
            $this->categoryId = $catalog->first()?->getKey();
        }

        $displayCatalog = $this->featuredOnly
            ? $featuredCatalog
            : $catalog->where('id', $this->categoryId)->values();
        $cart = $this->loadCart();
        $quote = null;
        $pricingContext = $this->pricingContexts->forIdentity(
            $this->customerId(),
            $this->minorProfileId(),
            PricingChannel::Storefront,
        );

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
            'categoryVisuals' => $this->categoryVisuals(),
            'memberPricingEligible' => $pricingContext->isMember(),
            'hasMemberOffers' => $catalog->flatMap->products->flatMap->options->contains(fn (ProductOption $option): bool => $option->member_price_baisa !== null),
        ]);
    }

    /** @return array<string, array{image: string, from: string, to: string}> */
    private function categoryVisuals(): array
    {
        $imageQuery = '?auto=format&fit=crop&w=640&h=960&q=75';

        return [
            'fresh' => ['image' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd'.$imageQuery, 'from' => '#0E7C7B', 'to' => '#0B3F3E'],
            'frozen' => ['image' => 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f'.$imageQuery, 'from' => '#2C5A7A', 'to' => '#10263A'],
            'sweets' => ['image' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587'.$imageQuery, 'from' => '#7A3E2E', 'to' => '#2E150F'],
            'cold' => ['image' => 'https://images.unsplash.com/photo-1461988091159-192b6df7054f'.$imageQuery, 'from' => '#1F3352', 'to' => '#0A1424'],
            'hot' => ['image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085'.$imageQuery, 'from' => '#5A3A1E', 'to' => '#24160A'],
            'tools' => ['image' => 'https://images.unsplash.com/photo-1442512595331-e89e73853f31'.$imageQuery, 'from' => '#3B3F45', 'to' => '#15181C'],
            'antiques' => ['image' => 'https://images.unsplash.com/photo-1519669556878-63bdad8a1a49'.$imageQuery, 'from' => '#6B5321', 'to' => '#2E2410'],
            'books' => ['image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794'.$imageQuery, 'from' => '#16263F', 'to' => '#6B5321'],
        ];
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
            return $this->resolveCart->execute($this->cartToken, $this->customerId(), false, $this->minorProfileId())
                ->load('items.productOption.product.category');
        } catch (ValidationException) {
            return null;
        }
    }

    private function resolveExistingCart(): Cart
    {
        return $this->resolveCart->execute($this->cartToken, $this->customerId(), false, $this->minorProfileId())
            ->load('items.productOption.product.category');
    }

    private function rememberCart(Cart $cart): void
    {
        $this->cartToken = (string) $cart->token;
        session()->put('store_cart_token', $this->cartToken);
    }

    private function customerId(): ?int
    {
        $minorProfile = auth('minor-profile')->user();

        if ($minorProfile !== null) {
            $minorProfile->loadMissing('familyMember');

            return is_numeric($minorProfile->familyMember?->customer_id)
                ? (int) $minorProfile->familyMember->customer_id
                : null;
        }

        $identifier = auth('customer')->user()?->getAuthIdentifier();

        return is_numeric($identifier) ? (int) $identifier : null;
    }

    private function minorProfileId(): ?int
    {
        $identifier = auth('minor-profile')->user()?->getAuthIdentifier();

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
