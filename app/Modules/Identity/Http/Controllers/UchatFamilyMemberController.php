<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Identity\Http\Requests\StoreCustomerFamilyMemberRequest;
use App\Modules\Identity\Http\Requests\UpdateCustomerFamilyMemberRequest;
use App\Modules\Identity\Http\Resources\UchatFamilyMemberResource as FamilyMemberResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UchatFamilyMemberController extends Controller
{
    public function index(UchatRequest $request): AnonymousResourceCollection
    {
        $customer = $request->customer();
        $familyMembers = $customer->familyMembers()
            ->with('minorProfile')
            ->oldest('birth_date')
            ->paginate($this->perPage($request));

        return FamilyMemberResource::collection($familyMembers);
    }

    public function store(StoreCustomerFamilyMemberRequest $request): JsonResponse
    {
        $customer = $request->customer();
        $customer->ensureProfileIsComplete();

        $familyMember = $customer->familyMembers()->create($request->validated());

        return FamilyMemberResource::make($familyMember->load('minorProfile'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(UchatRequest $request, int $familyMember): FamilyMemberResource
    {
        $familyMember = $request->customer()->familyMembers()->findOrFail($familyMember);

        return FamilyMemberResource::make($familyMember->load('minorProfile'));
    }

    public function update(UpdateCustomerFamilyMemberRequest $request, int $familyMember): FamilyMemberResource
    {
        $familyMember = $request->customer()->familyMembers()->findOrFail($familyMember);
        $customer = $request->customer();
        $customer->ensureProfileIsComplete();

        $familyMember->update($request->validated());

        return FamilyMemberResource::make($familyMember->refresh()->load('minorProfile'));
    }

    public function destroy(UchatRequest $request, int $familyMember): Response
    {
        $familyMember = $request->customer()->familyMembers()->findOrFail($familyMember);
        $customer = $request->customer();
        $customer->ensureProfileIsComplete();

        abort_if($familyMember->minorProfile()->exists(), response()->json([
            'code' => 'minor_account_lifecycle_required',
            'message' => __('api_accounts.minor_account_lifecycle_required'),
        ], 409));

        $familyMember->delete();

        return response()->noContent();
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
