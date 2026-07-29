<?php

namespace App\Actions;

use App\Enums\SeatAllocationState;
use App\Models\BookingSeatAllocation;
use App\Models\Event;
use App\Models\EventPriceTier;

class ResolveEventPriceTier
{
    public function execute(Event $event, int $requestedSeats, ?int $excludedBookingId = null, bool $lockForUpdate = false): ?EventPriceTier
    {
        $requestedSeats = max(1, $requestedSeats);
        $query = $event->priceTiers()
            ->where('is_active', true)
            ->where('price_baisa', '<=', $event->price_baisa)
            ->orderBy('position');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        foreach ($query->get() as $tier) {
            $usedSeatsQuery = BookingSeatAllocation::query()
                ->where('event_price_tier_id', $tier->id)
                ->whereIn('state', [SeatAllocationState::Held->value, SeatAllocationState::Reserved->value]);

            if ($excludedBookingId !== null) {
                $usedSeatsQuery->where('booking_id', '!=', $excludedBookingId);
            }

            $remainingSeats = $tier->seat_capacity - (int) $usedSeatsQuery->sum('seat_count');

            if ($remainingSeats >= $requestedSeats) {
                return $tier;
            }
        }

        return null;
    }
}
