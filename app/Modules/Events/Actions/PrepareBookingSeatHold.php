<?php

namespace App\Modules\Events\Actions;

use App\Enums\PaymentState;
use App\Enums\SeatAllocationState;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingSeatAllocation;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventPriceTier;
use App\Modules\Events\States\Booking\Approved;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrepareBookingSeatHold
{
    public function __construct(
        private ResolveEventPriceTier $resolveEventPriceTier,
        private RefreshBookingPriceForTier $refreshBookingPriceForTier,
    ) {}

    public function execute(int $bookingId): BookingSeatAllocation
    {
        $eventId = Booking::query()->whereKey($bookingId)->value('event_id');

        if ($eventId === null) {
            throw ValidationException::withMessages(['payment' => __('ui.messages.booking_not_found')]);
        }

        $requiresNewSignatures = false;

        $allocation = DB::transaction(function () use ($bookingId, $eventId, &$requiresNewSignatures): BookingSeatAllocation {
            $event = Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            $booking = Booking::query()
                ->whereKey($bookingId)
                ->with(['familyMembers.contract', 'paymentSchedule.installments.payments', 'seatAllocation'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $booking->state instanceof Approved) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.booking_must_be_approved_for_payment_plan'),
                ]);
            }

            $requestedSeats = $booking->familyMembers->count();

            if ($requestedSeats < 1) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.booking_requires_family_member'),
                ]);
            }

            $currentAllocation = $booking->seatAllocation;

            if ($currentAllocation?->state === SeatAllocationState::Reserved) {
                return $currentAllocation;
            }

            if ($currentAllocation?->state === SeatAllocationState::Held && $this->mustKeepExistingHold($currentAllocation)) {
                return $currentAllocation;
            }

            $unavailableSeats = (int) $event->seatAllocations()
                ->whereIn('state', [SeatAllocationState::Held->value, SeatAllocationState::Reserved->value])
                ->when($currentAllocation, fn ($query) => $query->where('booking_id', '!=', $booking->id))
                ->sum('seat_count');

            if ($event->seat_capacity - $unavailableSeats < $requestedSeats) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.not_enough_seats'),
                ]);
            }

            $tier = $this->resolveEventPriceTier->execute($event, $requestedSeats, $booking->id, true);
            $requiresNewSignatures = $this->refreshBookingPriceForTier->execute($booking, $tier);
            $heldAt = now();

            return $booking->seatAllocation()->updateOrCreate([], [
                'event_id' => $event->id,
                'event_price_tier_id' => $tier?->id,
                'payment_id' => null,
                'seat_count' => $requestedSeats,
                'state' => SeatAllocationState::Held,
                'tier_name' => $tier?->name,
                'tier_unit_price_baisa' => $tier instanceof EventPriceTier ? $tier->price_baisa : $event->price_baisa,
                'held_at' => $heldAt,
                'hold_expires_at' => $heldAt->copy()->addMinutes((int) config('byruhaa.seat_hold_minutes', 15)),
                'reserved_at' => null,
                'released_at' => null,
            ]);
        });

        if ($requiresNewSignatures) {
            throw ValidationException::withMessages([
                'payment' => __('ui.messages.price_changed_contracts_must_be_resigned'),
            ]);
        }

        return $allocation;
    }

    private function mustKeepExistingHold(BookingSeatAllocation $allocation): bool
    {
        if ($allocation->hold_expires_at?->isFuture()) {
            return true;
        }

        if ($allocation->payment_id === null) {
            return false;
        }

        return $allocation->payment()
            ->where('state', PaymentState::Pending->value)
            ->whereNotNull('provider_session_id')
            ->exists();
    }
}
