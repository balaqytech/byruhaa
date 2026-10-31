<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Requests\Integrations\Uchat\UchatIdentityRequest;
use App\Modules\Identity\Actions\RequestCustomerDeletion;
use App\Modules\Identity\Http\Requests\UchatDeletionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class UchatDeletionRequestController
{
    public function index(UchatIdentityRequest $request): JsonResponse
    {
        return JsonResource::collection($request->customer()->deletionRequests()->select(['id', 'status', 'reason', 'requested_at', 'reviewed_at'])->latest('id')->paginate($request->integer('per_page', 15)))->response();
    }

    public function store(UchatDeletionRequest $request, RequestCustomerDeletion $create): JsonResponse
    {
        $deletion = $create->execute($request->customer(), $request->validated('reason'));

        return response()->json(['data' => $deletion->only(['id', 'status', 'reason', 'requested_at', 'reviewed_at'])], 202);
    }
}
