<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateCustomerBooking;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerBookingRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerBookingController extends Controller
{
    public function index(Request $request, Customer $customer): AnonymousResourceCollection
    {
        $bookings = $customer->bookings()
            ->with(['event', 'familyMembers.familyMember', 'paymentSchedule.installments'])
            ->latest()
            ->paginate($this->perPage($request));

        return BookingResource::collection($bookings);
    }

    public function store(StoreCustomerBookingRequest $request, Customer $customer, CreateCustomerBooking $createCustomerBooking): JsonResponse
    {
        $validated = $request->validated();

        $booking = $createCustomerBooking->execute($customer, [
            'event_id' => (int) $validated['event_id'],
            'family_member_ids' => array_map('intval', $validated['family_member_ids']),
            'coupon_code' => $validated['coupon_code'] ?? null,
        ]);

        return BookingResource::make($booking)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer, Booking $booking): BookingResource
    {
        abort_unless($booking->customer_id === $customer->id, 404);

        return BookingResource::make(
            $booking->load(['event', 'familyMembers.familyMember', 'paymentSchedule.installments']),
        );
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
