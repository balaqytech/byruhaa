<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CreateOrderRequest;
use App\Http\Resources\Store\OrderResource;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Actions\ResolveCart;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(CreateOrderRequest $request, ResolveCart $resolveCart, CreateOrder $createOrder): JsonResponse
    {
        $minorProfile = $request->user('minor-profile');
        $customerId = null;
        $minorProfileId = null;

        if ($minorProfile !== null) {
            $minorProfile->loadMissing('familyMember');
            $customerId = (int) $minorProfile->familyMember->customer_id;
            $minorProfileId = (int) $minorProfile->id;
        } else {
            $customerIdentifier = $request->user('customer')?->getAuthIdentifier();
            $customerId = is_numeric($customerIdentifier) ? (int) $customerIdentifier : null;
        }

        $cart = $resolveCart->execute($request->header('X-Cart-Token'), $customerId, false, $minorProfileId);
        $order = $createOrder->execute($cart, [
            ...$request->validated(),
            'customer_id' => $customerId,
            'minor_profile_id' => $minorProfileId,
        ]);

        return OrderResource::make($order)->response()->setStatusCode(201);
    }
}
