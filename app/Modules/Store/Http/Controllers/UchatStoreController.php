<?php

namespace App\Modules\Store\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
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
use App\Modules\Store\Http\Requests\UchatCartItemRequest;
use App\Modules\Store\Http\Requests\UchatCartUpdateRequest;
use App\Modules\Store\Http\Requests\UchatIdentityRequest;
use App\Modules\Store\Http\Requests\UchatOrderRequest;
use App\Modules\Store\Http\Requests\UchatRequest;
use App\Modules\Store\Http\Resources\UchatCartResource;
use App\Modules\Store\Http\Resources\UchatCatalogResource;
use App\Modules\Store\Http\Resources\UchatOrderResource;
use App\Modules\Store\Http\Resources\UchatProductResource;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Services\UchatOwnerKey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UchatStoreController
{
    public function __construct(
        private UchatOwnerKey $ownerKey,
        private QuoteCart $quoteCart,
    ) {}

    public function catalog(BrowseCatalog $browseCatalog): AnonymousResourceCollection
    {
        return UchatCatalogResource::collection($browseCatalog->execute());
    }

    public function product(string $slug, BrowseProduct $browseProduct): JsonResource|JsonResponse
    {
        try {
            return UchatProductResource::make($browseProduct->execute($slug));
        } catch (ModelNotFoundException) {
            return $this->error('product_not_found', 'The product was not found.', 404);
        }
    }

    public function cart(UchatIdentityRequest $request, ResolveUchatCart $resolveCart, QuoteCart $quoteCart): JsonResponse
    {
        try {
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId());
            $quote = $this->quote($cart, $quoteCart);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return UchatCartResource::make($cart->load('items.productOption.product'))->withQuote($quote)->response();
    }

    public function addCartItem(UchatCartItemRequest $request, ResolveUchatCart $resolveCart, AddCartItem $addCartItem): JsonResponse
    {
        $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId());
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
        $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId());
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
        $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId());
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
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId(), false);
            $order = $createOrder->execute($cart, [
                ...$request->validated(),
                'customer_id' => $request->customerId(),
                'customer_phone' => $request->phone(),
            ]);
            $checkout = $initiatePayment->execute($order, $request->customerId());
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
            $order = $resolveOrder->execute($reference, $request->phone());
            $checkout = $initiatePayment->execute($order, $request->customerId());
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (PaymentGatewayException|RuntimeException) {
            return $this->error('payment_unavailable', 'The payment provider is temporarily unavailable.', 503);
        }

        return $this->orderResponse($order, $checkout);
    }

    public function orders(UchatIdentityRequest $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['items', 'statusHistory'])
            ->where('customer_phone', $request->phone())
            ->latest('id')
            ->paginate(20);

        return UchatOrderResource::collection($orders)->response();
    }

    public function order(string $reference, UchatIdentityRequest $request, ResolveUchatOrder $resolveOrder): JsonResponse
    {
        try {
            $order = $resolveOrder->execute($reference, $request->phone());
        } catch (ValidationException $exception) {
            return $this->validationError($exception, 404, 'order_not_found');
        }

        return UchatOrderResource::make($order)->response();
    }

    private function cartResponse(UchatRequest $request, ResolveUchatCart $resolveCart, QuoteCart $quoteCart): JsonResponse
    {
        try {
            $cart = $resolveCart->execute($this->ownerKey->forPhone($request->phone()), $request->customerId());
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
        if (! $cart->items()->exists()) {
            return ['subtotal_baisa' => 0, 'vat_baisa' => 0, 'total_baisa' => 0, 'currency' => 'OMR', 'items' => []];
        }

        $quote = $quoteCart->execute($cart);

        return [
            'subtotal_baisa' => $quote['subtotal_baisa'],
            'vat_baisa' => $quote['vat_baisa'],
            'total_baisa' => $quote['total_baisa'],
            'currency' => $quote['currency'],
            'items' => $quote['items'],
        ];
    }

    private function orderResponse(Order $order, PaymentCheckoutData $checkout, int $status = 200): JsonResponse
    {
        return UchatOrderResource::make($order->load(['items', 'statusHistory']))
            ->additional([
                'payment' => [
                    'status' => $checkout->status,
                    'checkout_url' => $checkout->checkoutUrl,
                    'expires_at' => $checkout->expiresAt?->format(DATE_ATOM),
                ],
            ])
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
