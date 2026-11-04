<?php

namespace App\Modules\Events\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Events\Actions\CreateCustomerBooking;
use App\Modules\Events\Http\Requests\StoreCustomerBookingRequest;
use App\Modules\Events\Http\Resources\UchatBookingResource as BookingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UchatBookingController extends Controller
{
    public function index(UchatRequest $request): AnonymousResourceCollection
    {
        $request->validate(['event_id' => ['sometimes', 'integer'], 'updated_since' => ['sometimes', 'date'], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $bookings = $request->customer()->bookings()
            ->with(['event.cancellation', 'familyMembers.familyMember', 'paymentSchedule.installments'])
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->when($request->filled('updated_since'), fn ($query) => $query->where('updated_at', '>=', $request->date('updated_since')))
            ->latest()
            ->paginate($this->perPage($request));

        return BookingResource::collection($bookings);
    }

    public function store(StoreCustomerBookingRequest $request, CreateCustomerBooking $createCustomerBooking): JsonResponse
    {
        $validated = $request->validated();
        $customer = $request->customer();

        $booking = $createCustomerBooking->execute($customer, [
            'event_id' => (int) $validated['event_id'],
            'family_member_ids' => array_map('intval', $validated['family_member_ids']),
            'coupon_code' => $validated['coupon_code'] ?? null,
            'idempotency_key' => $validated['idempotency_key'],
        ]);

        return BookingResource::make($booking)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(UchatRequest $request, string $reference): BookingResource
    {
        $booking = $request->customer()->bookings()->where('reference', $reference)->firstOrFail();

        return BookingResource::make(
            $booking->load(['event.cancellation', 'familyMembers.familyMember', 'paymentSchedule.installments']),
        );
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
