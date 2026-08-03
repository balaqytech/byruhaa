<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerProfileRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(Request $request, PhoneNumberNormalizer $phoneNumberNormalizer): AnonymousResourceCollection
    {
        $search = is_string($request->query('search')) ? trim($request->query('search')) : '';
        $phone = is_string($request->query('phone')) ? trim($request->query('phone')) : '';
        $email = is_string($request->query('email')) ? trim($request->query('email')) : '';

        $customers = Customer::query()
            ->when(
                $search !== '',
                fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                    ->where('email', 'like', '%'.$search.'%')
                    ->orWhere('phone_number', 'like', '%'.$phoneNumberNormalizer->normalize($search).'%')),
            )
            ->when(
                $phone !== '',
                fn (Builder $query): Builder => $query->where('phone_number', 'like', '%'.$phoneNumberNormalizer->normalize($phone).'%'),
            )
            ->when(
                $email !== '',
                fn (Builder $query): Builder => $query->where('email', 'like', '%'.$email.'%'),
            )
            ->latest()
            ->paginate($this->perPage($request));

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request, ByruhaaWebhookSender $webhookSender): JsonResponse
    {
        $customer = Customer::create(
            collect($request->validated())->except('password_confirmation')->all(),
        );

        $webhookSender->sendCustomerRegistered($customer);

        return CustomerResource::make($customer)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer): CustomerResource
    {
        return CustomerResource::make($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update(
            collect($request->validated())->except('password_confirmation')->all(),
        );

        return CustomerResource::make($customer->refresh());
    }

    public function updateProfile(UpdateCustomerProfileRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return CustomerResource::make($customer->refresh());
    }

    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
