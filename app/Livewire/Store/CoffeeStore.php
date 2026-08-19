<?php

namespace App\Livewire\Store;

use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\BrowseCatalog;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Actions\InitiateStorePayment;
use App\Modules\Store\Actions\QuoteCart;
use App\Modules\Store\Actions\RemoveCartItem;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Actions\UpdateCartItem;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
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

    public ?int $selectedOptionId = null;

    public string $pickupType = 'immediate';

    public ?string $pickupAt = null;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $customerEmail = '';

    public string $recipientName = '';

    public string $recipientPhone = '';

    public string $orderNote = '';

    public bool $checkoutOpen = false;

    public bool $submitting = false;

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
        $customer = auth('customer')->user();

        if ($customer !== null) {
            $this->customerName = (string) ($customer->name ?? '');
            $this->customerPhone = (string) ($customer->phone ?? '');
            $this->customerEmail = (string) ($customer->email ?? '');
        }
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
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
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function openCheckout(): void
    {
        try {
            $this->quoteCart->execute($this->resolveExistingCart());
            $this->checkoutOpen = true;
            $this->feedback = null;
            $this->idempotencyKey();
        } catch (ValidationException $exception) {
            $this->showValidation($exception);
        }
    }

    public function placeOrder(CreateOrder $createOrder, InitiateStorePayment $initiatePayment): void
    {
        if ($this->submitting) {
            return;
        }

        $this->submitting = true;
        $this->resetErrorBag();

        try {
            $validated = Validator::make([
                'idempotency_key' => $this->idempotencyKey(),
                'customer_name' => $this->customerName,
                'customer_phone' => $this->customerPhone,
                'customer_email' => $this->customerEmail ?: null,
                'recipient_name' => $this->recipientName ?: null,
                'recipient_phone' => $this->recipientPhone ?: null,
                'note' => $this->orderNote ?: null,
                'pickup_type' => $this->pickupType,
                'pickup_at' => $this->pickupAt ?: null,
            ], [
                'idempotency_key' => ['required', 'string', 'max:100'],
                'customer_name' => ['required', 'string', 'max:255'],
                'customer_phone' => ['required', 'string', 'max:32'],
                'customer_email' => ['nullable', 'email', 'max:255'],
                'recipient_name' => ['nullable', 'string', 'max:255'],
                'recipient_phone' => ['nullable', 'string', 'max:32'],
                'note' => ['nullable', 'string', 'max:5000'],
                'pickup_type' => ['required', 'in:immediate,scheduled'],
                'pickup_at' => ['nullable', 'date'],
            ])->validate();
            $validated['customer_id'] = $this->customerId();

            $order = $createOrder->execute($this->resolveExistingCart(), $validated);
            $payment = $initiatePayment->execute($order, $this->customerId());
            $this->resetCheckoutAttempt();

            $this->submitting = false;
            $this->redirect($payment->checkoutUrl, navigate: false);
        } catch (ValidationException $exception) {
            $this->submitting = false;
            $this->showValidation($exception);
        } catch (\Throwable $exception) {
            report($exception);
            $this->submitting = false;
            $this->addError('checkout', 'تعذر بدء الدفع الآن. حاول مرة أخرى بعد لحظات.');
        }
    }

    public function render(): View
    {
        $catalog = $this->browseCatalog->execute($this->categoryId);
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
            'cart' => $cart,
            'quote' => $quote,
            'orderingEnabled' => $this->settings->ordering_enabled,
        ]);
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
