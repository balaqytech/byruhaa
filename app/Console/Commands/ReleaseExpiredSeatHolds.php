<?php

namespace App\Console\Commands;

use App\Enums\PaymentState;
use App\Enums\SeatAllocationState;
use App\Modules\Events\Actions\ReleaseBookingSeats;
use App\Modules\Events\Models\BookingSeatAllocation;
use App\Modules\Events\States\Booking\Cancelled;
use App\Modules\Events\States\Booking\Rejected;
use App\Modules\Finance\Actions\ConfirmThawaniPayment;
use App\Modules\Finance\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('seats:release-expired-holds')]
#[Description('Confirm or cancel expired checkout sessions before releasing their seat holds')]
class ReleaseExpiredSeatHolds extends Command
{
    public function handle(ConfirmThawaniPayment $confirmThawaniPayment, ReleaseBookingSeats $releaseBookingSeats): int
    {
        $released = 0;

        BookingSeatAllocation::query()
            ->whereIn('state', [SeatAllocationState::Held->value, SeatAllocationState::Reserved->value])
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('state', SeatAllocationState::Held->value)
                        ->whereNotNull('hold_expires_at')
                        ->where('hold_expires_at', '<=', now());
                })->orWhereHas('booking', fn ($query) => $query->whereIn('state', [Cancelled::$name, Rejected::$name]));
            })
            ->with(['booking', 'payment'])
            ->orderBy('id')
            ->chunkById(100, function ($allocations) use ($confirmThawaniPayment, $releaseBookingSeats, &$released): void {
                foreach ($allocations as $allocation) {
                    $bookingWasCancelled = $allocation->booking->state instanceof Cancelled
                        || $allocation->booking->state instanceof Rejected;

                    try {
                        $payments = $bookingWasCancelled
                            ? Payment::query()
                                ->whereHas('bookingInstallment.paymentSchedule', fn ($query) => $query->where('booking_id', $allocation->booking_id))
                                ->where('state', PaymentState::Pending->value)
                                ->whereNotNull('provider_session_id')
                                ->get()
                            : collect(array_filter([$allocation->payment]));
                        $providerCapturedPayment = false;

                        foreach ($payments as $payment) {
                            if ($payment->state === PaymentState::Pending && $payment->provider_session_id) {
                                $payment = $confirmThawaniPayment->cancel($payment);
                            }

                            $providerCapturedPayment = $providerCapturedPayment || $payment->state === PaymentState::Paid;
                        }

                        if (! $bookingWasCancelled && $providerCapturedPayment) {
                            continue;
                        }

                        $result = $releaseBookingSeats->execute(
                            $allocation->booking,
                            ignoreActiveCheckoutSessions: true,
                            releaseCapturedBooking: $bookingWasCancelled,
                        );

                        if ($result?->state === SeatAllocationState::Released) {
                            $released++;
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        $this->warn("Seat allocation {$allocation->id} was kept because its checkout session could not be safely cancelled.");
                    }
                }
            });

        $this->info("Released {$released} seat allocation(s).");

        return self::SUCCESS;
    }
}
