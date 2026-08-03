<?php

namespace App\Modules\Events\Actions;

use App\Enums\PaymentState;
use App\Enums\SeatAllocationState;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingSeatAllocation;
use App\Modules\Events\Models\Event;
use App\Modules\Events\States\Booking\Cancelled;
use App\Modules\Events\States\Booking\Rejected;
use App\Modules\Finance\Models\Payment;
use Illuminate\Support\Facades\DB;

class ReleaseBookingSeats
{
    public function execute(
        Booking|Payment $source,
        bool $ignoreActiveCheckoutSessions = false,
        bool $releaseCapturedBooking = false,
    ): ?BookingSeatAllocation {
        $booking = $source instanceof Booking
            ? $source
            : $source->loadMissing('bookingInstallment.paymentSchedule.booking')->bookingInstallment->paymentSchedule->booking;

        return DB::transaction(function () use ($booking, $ignoreActiveCheckoutSessions, $releaseCapturedBooking): ?BookingSeatAllocation {
            Event::query()->whereKey($booking->event_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($booking->id)->with('seatAllocation')->lockForUpdate()->firstOrFail();
            $allocation = $booking->seatAllocation;

            if (! $allocation instanceof BookingSeatAllocation || $allocation->state === SeatAllocationState::Released) {
                return $allocation;
            }

            $mayReleaseCapturedBooking = $releaseCapturedBooking
                && ($booking->state instanceof Cancelled || $booking->state instanceof Rejected);

            if ((! $mayReleaseCapturedBooking && $this->hasCapturedPayment($booking))
                || (! $ignoreActiveCheckoutSessions && $this->hasActiveCheckoutSession($booking))) {
                return $allocation;
            }

            $allocation->forceFill([
                'state' => SeatAllocationState::Released,
                'hold_expires_at' => null,
                'released_at' => now(),
            ])->save();

            return $allocation->refresh();
        });
    }

    private function hasCapturedPayment(Booking $booking): bool
    {
        return Payment::query()
            ->whereHas('bookingInstallment.paymentSchedule', fn ($query) => $query->where('booking_id', $booking->id))
            ->whereIn('state', [PaymentState::Paid->value, PaymentState::PartiallyRefunded->value])
            ->exists();
    }

    private function hasActiveCheckoutSession(Booking $booking): bool
    {
        return Payment::query()
            ->whereHas('bookingInstallment.paymentSchedule', fn ($query) => $query->where('booking_id', $booking->id))
            ->where('state', PaymentState::Pending->value)
            ->whereNotNull('provider_session_id')
            ->exists();
    }
}
