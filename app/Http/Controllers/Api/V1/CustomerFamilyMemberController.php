<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerFamilyMemberRequest;
use App\Http\Requests\Api\V1\UpdateCustomerFamilyMemberRequest;
use App\Http\Resources\Api\V1\FamilyMemberResource;
use App\Models\Customer;
use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerFamilyMemberController extends Controller
{
    public function index(Request $request, Customer $customer): AnonymousResourceCollection
    {
        $familyMembers = $customer->familyMembers()
            ->oldest('birth_date')
            ->paginate($this->perPage($request));

        return FamilyMemberResource::collection($familyMembers);
    }

    public function store(StoreCustomerFamilyMemberRequest $request, Customer $customer): JsonResponse
    {
        $customer->ensureProfileIsComplete();

        $familyMember = $customer->familyMembers()->create($request->validated());

        return FamilyMemberResource::make($familyMember)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer, FamilyMember $familyMember): FamilyMemberResource
    {
        abort_unless($familyMember->customer_id === $customer->id, 404);

        return FamilyMemberResource::make($familyMember);
    }

    public function update(UpdateCustomerFamilyMemberRequest $request, Customer $customer, FamilyMember $familyMember): FamilyMemberResource
    {
        abort_unless($familyMember->customer_id === $customer->id, 404);
        $customer->ensureProfileIsComplete();

        $familyMember->update($request->validated());

        return FamilyMemberResource::make($familyMember->refresh());
    }

    public function destroy(Customer $customer, FamilyMember $familyMember): Response
    {
        abort_unless($familyMember->customer_id === $customer->id, 404);
        $customer->ensureProfileIsComplete();

        $familyMember->delete();

        return response()->noContent();
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
