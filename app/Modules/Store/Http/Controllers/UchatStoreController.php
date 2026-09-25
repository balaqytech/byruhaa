<?php

namespace App\Modules\Store\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Modules\Finance\Actions\InitiateWalletTopUp;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Contracts\MinorProfilePurchasing;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\BrowseCatalog;
use App\Modules\Store\Actions\BrowseProduct;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Actions\InitiateStorePayment;
use App\Modules\Store\Actions\QuoteCart;
use App\Modules\Store\Actions\RemoveCartItem;
use App\Modules\Store\Actions\ResolveUchatCart;
use App\Modules\Store\Actions\ResolveUchatOrder;
use App\Modules\Store\Actions\UpdateCartItem;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Http\Requests\UchatCartItemRequest;
use App\Modules\Store\Http\Requests\UchatCartUpdateRequest;
use App\Modules\Store\Http\Requests\UchatIdentityRequest;
use App\Modules\Store\Http\Requests\UchatOrderRequest;
use App\Modules\Store\Http\Requests\UchatRequest;
use App\Modules\Store\Http\Requests\UchatWalletTopUpRequest;
use App\Modules\Store\Http\Resources\UchatCartResource;
use App\Modules\Store\Http\Resources\UchatCatalogResource;
use App\Modules\Store\Http\Resources\UchatOrderResource;
use App\Modules\Store\Http\Resources\UchatProductResource;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Services\PricingContextResolver;
use App\Modules\Store\Services\UchatOwnerKey;
use App\Support\Money\MoneyFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UchatStoreController
{
    public function __construct(
        private UchatOwnerKey $ownerKey,
        private QuoteCart $quoteCart,
        private MinorProfilePurchasing $minorProfiles,
        private PricingContextResolver $pricingContexts,
    ) {}

    public function catalog(UchatIdentityRequest $request, BrowseCatalog $browseCatalog): AnonymousResourceCollection
    {
        $context = $this->pricingContexts->forIdentity($request->customerId(), channel: PricingChannel::Uchat);

        return UchatCatalogResource::collection($browseCatalog->execute())
            ->additional(['pricing_tier' => $context->tier->value]);
    }

    public function product(string $slug, UchatIdentityRequest $request, BrowseProduct $browseProduct): JsonResource|JsonResponse
    {
        try {
            $context = $this->pricingContexts->forIdentity($request->customerId(), channel: PricingChannel::Uchat);

            return UchatProductResource::make($browseProduct->execute($slug))
                ->additional(['pricing_tier' => $context->tier->value]);
        } catch (ModelNotFoundException) {
            return $this->error('product_not_found', 'The product was not found.', 404);
        }
    }

    public function cart(UchatIdentityRequest $request, ResolveUchatCart $resolveCart, QuoteCart $quoteCart): JsonResponse
    {
        try {
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId(), true, $this->selectedMinorProfileId($request));
            $quote = $this->quote($cart, $quoteCart);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return UchatCartResource::make($cart->load('items.productOption.product'))->withQuote($quote)->response();
    }

    public function addCartItem(UchatCartItemRequest $request, ResolveUchatCart $resolveCart, AddCartItem $addCartItem): JsonResponse
    {
        try {
            $minorProfileId = $this->selectedMinorProfileId($request);
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId(), true, $minorProfileId);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        $option = ProductOption::query()->where('sku', $request->string('sku')->toString())->with('product.category')->first();

        if (! $option instanceof ProductOption) {
            return $this->error('product_unavailable', 'The product option is not available.', 422);
        }

        try {
            $addCartItem->execute($cart, $option, $request->integer('quantity'), $request->input('note'), true);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return $this->cartResponse($request, $resolveCart, $this->quoteCart);
    }

    public function updateCartItem(string $sku, UchatCartUpdateRequest $request, ResolveUchatCart $resolveCart, UpdateCartItem $updateCartItem): JsonResponse
    {
        try {
            $minorProfileId = $this->selectedMinorProfileId($request);
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId(), true, $minorProfileId);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        $item = $cart->items()->whereHas('productOption', fn ($query) => $query->where('sku', $sku))->first();

        if (! $item) {
            return $this->error('cart_item_not_found', 'The cart item was not found.', 404);
        }

        try {
            $updateCartItem->execute($cart, $item, $request->integer('quantity'), $request->input('note'), true);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return $this->cartResponse($request, $resolveCart, $this->quoteCart);
    }

    public function removeCartItem(string $sku, UchatIdentityRequest $request, ResolveUchatCart $resolveCart, RemoveCartItem $removeCartItem): JsonResponse
    {
        try {
            $minorProfileId = $this->selectedMinorProfileId($request);
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId(), true, $minorProfileId);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        $item = $cart->items()->whereHas('productOption', fn ($query) => $query->where('sku', $sku))->first();

        if (! $item) {
            return $this->error('cart_item_not_found', 'The cart item was not found.', 404);
        }

        $removeCartItem->execute($cart, $item);

        return $this->cartResponse($request, $resolveCart, $this->quoteCart);
    }

    public function createOrder(UchatOrderRequest $request, ResolveUchatCart $resolveCart, CreateOrder $createOrder, InitiateStorePayment $initiatePayment): JsonResponse
    {
        try {
            $minorProfileId = $this->selectedMinorProfileId($request);
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId(), false, $minorProfileId);
            $order = $createOrder->execute($cart, [
                ...$request->validated(),
                'customer_id' => $request->customerId(),
                'customer_phone' => $request->phone(),
                'minor_profile_id' => $minorProfileId,
            ]);
            $checkout = $order->payment_method === 'wallet'
                ? null
                : $initiatePayment->execute($order, $request->customerId());
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (PaymentGatewayException|RuntimeException) {
            return $this->error('payment_unavailable', 'The payment provider is temporarily unavailable.', 503);
        }

        return $this->orderResponse($order, $checkout, 201);
    }

    public function initiatePayment(string $reference, UchatIdentityRequest $request, ResolveUchatOrder $resolveOrder, InitiateStorePayment $initiatePayment): JsonResponse
    {
        try {
            $order = $resolveOrder->execute($reference, $request->phone(), $this->selectedMinorProfileId($request));
            $checkout = $order->payment_method === 'wallet'
                ? null
                : $initiatePayment->execute($order, $request->customerId());
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (PaymentGatewayException|RuntimeException) {
            return $this->error('payment_unavailable', 'The payment provider is temporarily unavailable.', 503);
        }

        return $this->orderResponse($order, $checkout);
    }

    public function wallet(UchatIdentityRequest $request, WalletService $wallets): JsonResponse
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            return $this->error('wallet_unavailable', 'Wallets are currently unavailable.', 404);
        }

        try {
            $minorProfileId = $this->selectedMinorProfileId($request);

            if ($minorProfileId === null) {
                throw ValidationException::withMessages(['minor_profile_id' => 'Select a child account to view its wallet.']);
            }

            $this->minorProfiles->forGuardian($minorProfileId, (int) $request->customerId());

            $summary = $wallets->summary($minorProfileId);
            $wallet = $wallets->walletForMinorProfile($minorProfileId);
            $movements = $wallet->movements()->latest('id')->limit(50)->get();
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json([
            'wallet_id' => $summary->walletId,
            'minor_profile_id' => $summary->minorProfileId,
            'balance_baisa' => $summary->balanceBaisa,
            ...$this->walletBalances($wallet),
            'currency' => $summary->currency,
            'status' => $summary->status,
            'movements' => $movements->map(fn ($movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'order_reference' => $movement->order_reference,
                'credit_baisa' => $movement->credit_baisa,
                'debit_baisa' => $movement->debit_baisa,
                'balance_after_baisa' => $movement->balance_after_baisa,
                'created_at' => $movement->created_at?->toJSON(),
            ])->values()->all(),
        ]);
    }

    public function walletTopUp(UchatWalletTopUpRequest $request, InitiateWalletTopUp $initiateTopUp): JsonResponse
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            return $this->error('wallet_unavailable', 'Wallets are currently unavailable.', 404);
        }

        try {
            $minorProfileId = $this->selectedMinorProfileId($request);

            if ($minorProfileId === null) {
                throw ValidationException::withMessages(['minor_profile_id' => 'Select a child account before adding wallet funds.']);
            }

            $this->minorProfiles->forGuardian($minorProfileId, (int) $request->customerId());

            $operationKey = $request->string('idempotency_key')->toString();
            $amountBaisa = MoneyFactory::omrStringToBaisa((string) $request->validated('amount_omr'));
            $checkout = $initiateTopUp->execute(
                $minorProfileId,
                $operationKey,
                $amountBaisa,
                URL::temporarySignedRoute('customer.minor-profiles.wallet.top-up.success', now()->addHours(12), [
                    'minorProfile' => $minorProfileId,
                    'operationKey' => $operationKey,
                ]),
                URL::temporarySignedRoute('customer.minor-profiles.wallet.top-up.cancel', now()->addHours(12), [
                    'minorProfile' => $minorProfileId,
                    'operationKey' => $operationKey,
                ]),
            );
            $topUp = WalletTopUp::query()->where('operation_key', $operationKey)->firstOrFail();
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (PaymentGatewayException|RuntimeException) {
            return $this->error('payment_unavailable', 'The payment provider is temporarily unavailable.', 503);
        }

        return response()->json([
            'top_up_reference' => $topUp->reference,
            'payment_reference' => $checkout->paymentReference,
            'amount_baisa' => $checkout->amountBaisa,
            'currency' => $checkout->currency,
            'status' => $checkout->status,
            'checkout_url' => $checkout->checkoutUrl,
            'expires_at' => $checkout->expiresAt?->format(DATE_ATOM),
        ], 201);
    }

    public function walletMovements(UchatIdentityRequest $request, WalletService $wallets): JsonResponse
    {
        $wallet = $this->readableWallet($request, $wallets);
        $movements = $wallet->movements()->latest('id')->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $movements->map(fn ($movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'order_reference' => $movement->order_reference,
                'credit_baisa' => $movement->credit_baisa,
                'debit_baisa' => $movement->debit_baisa,
                'balance_after_baisa' => $movement->balance_after_baisa,
                'created_at' => $movement->created_at?->toJSON(),
            ]),
            'meta' => ['current_page' => $movements->currentPage(), 'last_page' => $movements->lastPage(), 'per_page' => $movements->perPage(), 'total' => $movements->total()],
        ]);
    }

    public function walletTopUpStatus(string $reference, UchatIdentityRequest $request, WalletService $wallets): JsonResponse
    {
        $wallet = $this->readableWallet($request, $wallets);
        $topUp = $wallet->topUps()->where('reference', $reference)->with('payment')->first();
        if (! $topUp instanceof WalletTopUp) {
            return $this->error('top_up_not_found', 'The wallet top-up was not found.', 404);
        }

        return response()->json(['data' => [
            'top_up_reference' => $topUp->reference,
            'minor_profile_id' => $wallet->minor_profile_id,
            'status' => $topUp->status,
            'payment_status' => $topUp->payment?->state?->value,
            'payment_reference' => $topUp->payment?->reference,
            'amount_baisa' => $topUp->amount_baisa,
            'currency' => $topUp->currency,
            'credited_at' => $topUp->credited_at?->toJSON(),
            'reserved_refund_baisa' => $topUp->reserved_refund_baisa,
            'eligible_refund_baisa' => $this->eligibleRefund($topUp),
            'refund_deadline_at' => $topUp->refund_deadline_at?->toJSON(),
        ]]);
    }

    private function readableWallet(UchatIdentityRequest $request, WalletService $wallets): Wallet
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            abort(response()->json(['code' => 'wallet_unavailable', 'message' => 'Wallets are currently unavailable.'], 404));
        }
        $profileId = $this->selectedMinorProfileId($request);
        if ($profileId === null) {
            throw ValidationException::withMessages(['minor_profile_id' => 'Select a child account to view its wallet.']);
        }
        $this->minorProfiles->forGuardian($profileId, (int) $request->customerId());

        return $wallets->walletForMinorProfile($profileId);
    }

    /** @return array{available_balance_baisa: int, reserved_balance_baisa: int, eligible_refund_baisa: int} */
    private function walletBalances(Wallet $wallet): array
    {
        $topUps = $wallet->topUps()->get();
        $spendable = (int) $topUps->whereIn('status', ['credited', 'refunding'])->sum(fn (WalletTopUp $topUp): int => max(0, $topUp->spendable_baisa - $topUp->reserved_refund_baisa));

        return [
            'available_balance_baisa' => $wallet->status === 'active' ? max(0, min($wallet->balance_baisa, $spendable)) : 0,
            'reserved_balance_baisa' => (int) $topUps->sum('reserved_refund_baisa'),
            'eligible_refund_baisa' => (int) $topUps->sum(fn (WalletTopUp $topUp): int => $this->eligibleRefund($topUp)),
        ];
    }

    private function eligibleRefund(WalletTopUp $topUp): int
    {
        return in_array($topUp->status, ['credited', 'refunding'], true) && $topUp->refund_deadline_at?->isFuture()
            ? max(0, min($topUp->spendable_baisa, $topUp->refundable_baisa) - $topUp->reserved_refund_baisa)
            : 0;
    }

    public function orders(UchatIdentityRequest $request): JsonResponse
    {
        try {
            $minorProfileId = $this->selectedMinorProfileId($request);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        $orders = Order::query()
            ->with(['items', 'statusHistory'])
            ->where('customer_phone', $request->phone())
            ->when($minorProfileId !== null, fn ($query) => $query->where('minor_profile_id', $minorProfileId))
            ->latest('id')
            ->paginate(20);

        return UchatOrderResource::collection($orders)->response();
    }

    public function order(string $reference, UchatIdentityRequest $request, ResolveUchatOrder $resolveOrder): JsonResponse
    {
        try {
            $order = $resolveOrder->execute($reference, $request->phone(), $this->selectedMinorProfileId($request));
        } catch (ValidationException $exception) {
            return $this->validationError($exception, 404, 'order_not_found');
        }

        return UchatOrderResource::make($order)->response();
    }

    private function cartResponse(UchatRequest $request, ResolveUchatCart $resolveCart, QuoteCart $quoteCart): JsonResponse
    {
        try {
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId(), true, $this->selectedMinorProfileId($request));
            $quote = $this->quote($cart, $quoteCart);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (RuntimeException) {
            return $this->error('payment_unavailable', 'The payment provider is temporarily unavailable.', 503);
        }

        return UchatCartResource::make($cart->load('items.productOption.product'))
            ->withQuote($quote)
            ->response();
    }

    /** @return array<string, mixed> */
    private function quote(Cart $cart, QuoteCart $quoteCart): array
    {
        $quote = $quoteCart->executeOrEmpty($cart, PricingChannel::Uchat);

        return [
            'subtotal_baisa' => $quote['subtotal_baisa'],
            'vat_baisa' => $quote['vat_baisa'],
            'total_baisa' => $quote['total_baisa'],
            'regular_total_baisa' => $quote['regular_total_baisa'],
            'discount_baisa' => $quote['discount_baisa'],
            'pricing_tier' => $quote['pricing_tier'],
            'currency' => $quote['currency'],
            'items' => $quote['items'],
        ];
    }

    private function selectedMinorProfileId(UchatRequest $request): ?int
    {
        $profileId = $request->minorProfileId();

        if ($profileId !== null) {
            $customerId = $request->customerId();

            if ($customerId === null) {
                throw ValidationException::withMessages(['minor_profile_id' => 'A guardian account is required for a minor profile.']);
            }

            $this->minorProfiles->forGuardian($profileId, $customerId);
        }

        return $profileId;
    }

    private function orderResponse(Order $order, ?PaymentCheckoutData $checkout, int $status = 200): JsonResponse
    {
        $payment = $checkout === null
            ? [
                'method' => 'wallet',
                'status' => 'confirmation_required',
                'confirmation_url' => URL::temporarySignedRoute('store.orders.wallet.confirm.link', now()->addHours(12), ['order' => $order->payment_token]),
            ]
            : [
                'method' => 'thawani',
                'status' => $checkout->status,
                'checkout_url' => $checkout->checkoutUrl,
                'expires_at' => $checkout->expiresAt?->format(DATE_ATOM),
            ];

        return UchatOrderResource::make($order->load(['items', 'statusHistory']))
            ->additional(['payment' => $payment])
            ->response()
            ->setStatusCode($status);
    }

    private function validationError(ValidationException $exception, int $status = 422, string $code = 'validation_failed'): JsonResponse
    {
        return $this->error($code, 'The request could not be completed.', $status, $exception->errors());
    }

    /** @param array<string, mixed> $errors */
    private function error(string $code, string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json(array_filter(['code' => $code, 'message' => $message, 'errors' => $errors], fn ($value): bool => $value !== []), $status);
    }
}
