<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\AddCartItemRequest;
use App\Http\Requests\Store\UpdateCartItemRequest;
use App\Http\Resources\Store\CartItemResource;
use App\Http\Resources\Store\CartResource;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\RemoveCartItem;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Actions\UpdateCartItem;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\CartItem;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request, ResolveCart $resolveCart): JsonResponse
    {
        ['customerId' => $customerId, 'minorProfileId' => $minorProfileId] = $this->storeIdentity($request);
        $cart = $resolveCart->execute($request->header('X-Cart-Token'), $customerId, false, $minorProfileId);

        return CartResource::make($cart->load('items.productOption.product'))->response();
    }

    public function add(AddCartItemRequest $request, ResolveCart $resolveCart, AddCartItem $addCartItem): JsonResponse
    {
        ['customerId' => $customerId, 'minorProfileId' => $minorProfileId] = $this->storeIdentity($request);
        $cart = $resolveCart->execute($request->header('X-Cart-Token'), $customerId, true, $minorProfileId);
        $item = $addCartItem->execute(
            $cart,
            ProductOption::query()->findOrFail($request->integer('product_option_id')),
            $request->integer('quantity'),
            $request->input('note'),
        );

        return CartItemResource::make($item)->additional(['cart_token' => $cart->token])->response()->setStatusCode(201);
    }

    public function update(UpdateCartItemRequest $request, Cart $cart, CartItem $item, ResolveCart $resolveCart, UpdateCartItem $updateCartItem): JsonResponse
    {
        ['customerId' => $customerId, 'minorProfileId' => $minorProfileId] = $this->storeIdentity($request);
        $cart = $resolveCart->execute($cart->token, $customerId, false, $minorProfileId);
        $updated = $updateCartItem->execute($cart, $item, $request->integer('quantity'), $request->input('note'));

        return CartItemResource::make($updated)->response();
    }

    public function remove(Request $request, Cart $cart, CartItem $item, ResolveCart $resolveCart, RemoveCartItem $removeCartItem): JsonResponse
    {
        ['customerId' => $customerId, 'minorProfileId' => $minorProfileId] = $this->storeIdentity($request);
        $cart = $resolveCart->execute($cart->token, $customerId, false, $minorProfileId);
        $removeCartItem->execute($cart, $item);

        return response()->json(status: 204);
    }

    /** @return array{customerId: int|null, minorProfileId: int|null} */
    private function storeIdentity(Request $request): array
    {
        $minorProfile = $request->user('minor-profile');

        if ($minorProfile !== null) {
            $minorProfile->loadMissing('familyMember');

            return [
                'customerId' => (int) $minorProfile->familyMember->customer_id,
                'minorProfileId' => (int) $minorProfile->id,
            ];
        }

        $customerIdentifier = $request->user('customer')?->getAuthIdentifier();

        return [
            'customerId' => is_numeric($customerIdentifier) ? (int) $customerIdentifier : null,
            'minorProfileId' => null,
        ];
    }
}
