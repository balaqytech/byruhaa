<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Requests\Integrations\Uchat\UchatCustomerRequest;
use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Identity\Actions\CreateCustomerAccount;
use App\Modules\Identity\Http\Resources\UchatCustomerResource as CustomerResource;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class UchatCustomerController
{
    public function show(UchatRequest $request, UchatAccountController $accounts): JsonResponse
    {
        $customer = $request->customer();
        $account = $accounts->account($request);

        return response()->json(['data' => [...$account->getData(true)['data'], ...CustomerResource::make($customer)->resolve($request)]]);
    }

    public function store(UchatCustomerRequest $request, CreateCustomerAccount $create): JsonResponse
    {
        abort_if($request->customerId() !== null, response()->json(['code' => 'customer_already_exists', 'message' => __('api_uchat.customer_already_exists')], 409));

        try {
            $customer = $create->execute([...$request->safe()->except('password_confirmation'), 'phone_number' => $request->phone()]);
        } catch (UniqueConstraintViolationException) {
            abort(response()->json(['code' => 'customer_already_exists', 'message' => __('api_uchat.customer_already_exists')], 409));
        }

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    public function update(UchatCustomerRequest $request): CustomerResource
    {
        $customer = $request->customer();
        if ($request->has('email') && $request->validated('email') !== $customer->email) {
            $customer->forceFill(['email_verified_at' => null]);
        }
        $customer->update($request->validated());

        return CustomerResource::make($customer->refresh());
    }
}
