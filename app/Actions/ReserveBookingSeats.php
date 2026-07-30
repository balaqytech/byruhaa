<?php

namespace App\Actions;

use App\Enums\EventInterestStatus;
use App\Enums\SeatAllocationState;
use App\Models\Booking;
use App\Models\BookingSeatAllocation;
use App\Models\Event;
use App\Models\EventInterest;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveBookingSeats
{
    public function __construct(private ResolveEventPriceTier $resolveEventPriceTier) {}

    public function execute(Payment $payment): ?BookingSeatAllocation
    {
        $payment->loadMissing('bookingInstallment.paymentSchedule.booking');
        $bookingId = $payment->bookingInstallment->paymentSchedule->booking_id;
        $eventId = $payment->bookingInstallment->paymentSchedule->booking->event_id;

        return DB::transaction(function () use ($payment, $bookingId, $eventId): ?BookingSeatAllocation {
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($bookingId)->with('seatAllocation')->lockForUpdate()->firstOrFail();
            $requestedSeats = $booking->familyMembers()->count();

            if ($requestedSeats < 1) {
                return null;
            }

            $allocation = $booking->seatAllocation;

            if ($allocation?->state === SeatAllocationState::Reserved) {
                return $allocation;
            }

            if (! $allocation instanceof BookingSeatAllocation || $allocation->state === SeatAllocationState::Released) {
                $unavailableSeats = $event->unavailableSeatsCount();

                if ($event->seat_capacity - $unavailableSeats < $requestedSeats) {
                    throw ValidationException::withMessages(['payment' => __('ui.messages.not_enough_seats')]);
                }

                $tier = $this->resolveEventPriceTier->execute($event, $requestedSeats, $booking->id, true);
                $allocationData = [
                    'event_id' => $event->id,
                    'event_price_tier_id' => $tier?->id,
                    'seat_count' => $requestedSeats,
                    'state' => SeatAllocationState::Held,
                    'tier_name' => $tier?->name,
                    'tier_unit_price_baisa' => $booking->unit_price_baisa,
                    'held_at' => now(),
                    'released_at' => null,
                ];

                if ($allocation instanceof BookingSeatAllocation) {
                    $allocation->forceFill($allocationData)->save();
                } else {
                    $allocation = $booking->seatAllocation()->create($allocationData);
                }
            }

            $allocation->forceFill([
                'payment_id' => $allocation->payment_id ?? $payment->id,
                'state' => SeatAllocationState::Reserved,
                'hold_expires_at' => null,
                'reserved_at' => $allocation->reserved_at ?? $payment->paid_at ?? now(),
                'released_at' => null,
            ])->save();

            EventInterest::query()
                ->where('customer_id', $booking->customer_id)
                ->where('event_id', $event->id)
                ->whereIn('status', [
                    EventInterestStatus::Interested->value,
                    EventInterestStatus::BookingStarted->value,
                ])
                ->update([
                    'status' => EventInterestStatus::Converted->value,
                    'booking_id' => $booking->id,
                    'converted_at' => $payment->paid_at ?? now(),
                ]);

            return $allocation->refresh();
        });
    }
}
