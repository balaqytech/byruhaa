<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Requests\Integrations\Uchat\UchatIdentityRequest;
use App\Modules\Finance\Http\Resources\UchatPaymentResource;
use App\Modules\Finance\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UchatPaymentController
{
    public function index(UchatIdentityRequest $request): AnonymousResourceCollection
    {
        return UchatPaymentResource::collection($this->payments($request)->with('bookingInstallment')->latest()->paginate($request->integer('per_page', 15)));
    }

    public function show(UchatIdentityRequest $request, string $reference): UchatPaymentResource
    {
        return UchatPaymentResource::make($this->payments($request)->where('reference', $reference)->with('bookingInstallment')->firstOrFail());
    }

    /** @return Builder<Payment> */
    private function payments(UchatIdentityRequest $request): Builder
    {
        $customerId = $request->customer()->id;

        return Payment::query()->whereHas('bookingInstallment.paymentSchedule.booking', fn (Builder $query): Builder => $query->where('customer_id', $customerId));
    }
}
