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
        $customerIdentifier = $request->user('customer')?->getAuthIdentifier();
        $customerId = is_numeric($customerIdentifier) ? (int) $customerIdentifier : null;
        $cart = $resolveCart->execute($request->header('X-Cart-Token'), $customerId, false);

        return CartResource::make($cart->load('items.productOption.product'))->response();
    }

    public function add(AddCartItemRequest $request, ResolveCart $resolveCart, AddCartItem $addCartItem): JsonResponse
    {
        $customerIdentifier = $request->user('customer')?->getAuthIdentifier();
        $customerId = is_numeric($customerIdentifier) ? (int) $customerIdentifier : null;
        $cart = $resolveCart->execute($request->header('X-Cart-Token'), $customerId);
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
        $customerIdentifier = $request->user('customer')?->getAuthIdentifier();
        $customerId = is_numeric($customerIdentifier) ? (int) $customerIdentifier : null;
        $cart = $resolveCart->execute($cart->token, $customerId, false);
        $updated = $updateCartItem->execute($cart, $item, $request->integer('quantity'), $request->input('note'));

        return CartItemResource::make($updated)->response();
    }

    public function remove(Request $request, Cart $cart, CartItem $item, ResolveCart $resolveCart, RemoveCartItem $removeCartItem): JsonResponse
    {
        $customerIdentifier = $request->user('customer')?->getAuthIdentifier();
        $customerId = is_numeric($customerIdentifier) ? (int) $customerIdentifier : null;
        $cart = $resolveCart->execute($cart->token, $customerId, false);
        $removeCartItem->execute($cart, $item);

        return response()->json(status: 204);
    }
}
