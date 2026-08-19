<?php

namespace App\Livewire\Store;

use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Actions\InitiateStorePayment;
use App\Modules\Store\Actions\QuoteCart;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Checkout extends Component
{
    private const CHECKOUT_IDEMPOTENCY_KEYS_SESSION = 'store_checkout_idempotency_keys';

    private const LEGACY_CHECKOUT_IDEMPOTENCY_KEY_SESSION = 'store_checkout_idempotency_key';

    protected QuoteCart $quoteCart;

    protected ResolveCart $resolveCart;

    protected StoreSettings $settings;

    public string $pickupType = 'immediate';

    public ?string $pickupAt = null;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $customerEmail = '';

    public string $recipientName = '';

    public string $recipientPhone = '';

    public string $orderNote = '';

    public bool $submitting = false;

    public ?string $cartError = null;

    public ?string $cartToken = null;

    public function boot(QuoteCart $quoteCart, ResolveCart $resolveCart, StoreSettings $settings): void
    {
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

    public function backToStore(): void
    {
        $this->redirect(route('coffee').'#menu', navigate: true);
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
            $this->addError('checkout', 'تعذّر بدء الدفع الآن. حاول مرة أخرى بعد لحظات.');
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

        return view('livewire.store.checkout', [
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
            $this->addError($field, is_array($messages) ? (string) ($messages[0] ?? 'تعذّر تنفيذ الطلب.') : (string) $messages);
        }
    }
}
