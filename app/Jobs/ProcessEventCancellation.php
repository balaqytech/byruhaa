<?php

namespace App\Jobs;

use App\Actions\ConfirmThawaniPayment;
use App\Actions\RefundPayment;
use App\Actions\ReleaseBookingSeats;
use App\Actions\ReverseAffiliateCommission;
use App\Enums\BookingInstallmentState;
use App\Enums\EventCancellationStatus;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Models\AffiliateCommission;
use App\Models\Booking;
use App\Models\EventCancellation;
use App\Models\Payment;
use App\States\Booking\Approved;
use App\States\Booking\Cancelled;
use App\States\Booking\PendingReview;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessEventCancellation implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $eventCancellationId) {}

    /**
     * Execute the job.
     */
    public function handle(
        ConfirmThawaniPayment $confirmPayment,
        RefundPayment $refundPayment,
        ReleaseBookingSeats $releaseBookingSeats,
        ReverseAffiliateCommission $reverseAffiliateCommission,
    ): void {
        $cancellation = EventCancellation::query()->with('event')->findOrFail($this->eventCancellationId);
        $cancellation->forceFill([
            'status' => EventCancellationStatus::Processing,
            'processing_started_at' => $cancellation->processing_started_at ?? now(),
            'errors' => null,
        ])->save();

        $errors = [];
        $bookings = Booking::query()->where('event_id', $cancellation->event_id)->get();

        foreach ($bookings as $booking) {
            try {
                $this->processBooking($booking, $cancellation, $confirmPayment, $refundPayment, $releaseBookingSeats);
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = ['booking_id' => $booking->id, 'message' => $exception->getMessage()];
            }
        }

        $commissions = AffiliateCommission::query()
            ->whereHas('booking', fn ($query) => $query->where('event_id', $cancellation->event_id))
            ->whereHas('payment', fn ($query) => $query->where('state', PaymentState::Refunded->value))
            ->get();

        foreach ($commissions as $commission) {
            try {
                $reverseAffiliateCommission->execute($commission, $cancellation);
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = ['affiliate_commission_id' => $commission->id, 'message' => $exception->getMessage()];
            }
        }

        $payments = $this->capturedPayments($cancellation)->get();
        $remainingRefundBaisa = $payments->sum(fn (Payment $payment): int => $payment->refundableAmountBaisa());
        $succeededRefunds = $this->eventPayments($cancellation)
            ->whereHas('refunds', fn ($query) => $query->where('state', PaymentRefundState::Succeeded->value))
            ->withSum(['refunds as succeeded_refund_amount_baisa' => fn ($query) => $query->where('state', PaymentRefundState::Succeeded->value)], 'amount_baisa')
            ->get();

        if ($remainingRefundBaisa > 0 && $errors === []) {
            $errors[] = ['message' => 'توجد مبالغ مدفوعة لم يكتمل استردادها.'];
        }

        $unreversedCommissionsCount = AffiliateCommission::query()
            ->whereHas('booking', fn ($query) => $query->where('event_id', $cancellation->event_id))
            ->whereDoesntHave('reversal')
            ->count();

        if ($unreversedCommissionsCount > 0) {
            $errors[] = [
                'message' => "توجد {$unreversedCommissionsCount} عمولات تسويق لم تكتمل تسويتها.",
            ];
        }

        $cancellation->forceFill([
            'status' => $errors === [] ? EventCancellationStatus::Completed : EventCancellationStatus::NeedsAttention,
            'refunded_payments_count' => $succeededRefunds->count(),
            'refunded_amount_baisa' => $succeededRefunds->sum('succeeded_refund_amount_baisa'),
            'errors' => $errors === [] ? null : $errors,
            'completed_at' => $errors === [] ? now() : null,
        ])->save();
    }

    public function uniqueId(): string
    {
        return (string) $this->eventCancellationId;
    }

    public function failed(Throwable $exception): void
    {
        EventCancellation::query()->whereKey($this->eventCancellationId)->update([
            'status' => EventCancellationStatus::NeedsAttention->value,
            'errors' => [['message' => $exception->getMessage()]],
        ]);
    }

    private function processBooking(Booking $booking, EventCancellation $cancellation, ConfirmThawaniPayment $confirmPayment, RefundPayment $refundPayment, ReleaseBookingSeats $releaseBookingSeats): void
    {
        $failure = null;

        try {
            $pendingPayments = $this->bookingPayments($booking)
                ->where('state', PaymentState::Pending->value)
                ->whereNotNull('provider_session_id')
                ->get();

            foreach ($pendingPayments as $payment) {
                $confirmPayment->cancel($payment);
            }

            foreach ($this->bookingPayments($booking)->with('refunds')->get() as $payment) {
                $amountBaisa = $payment->refundableAmountBaisa();

                if ($amountBaisa > 0) {
                    $refundPayment->execute($payment, $amountBaisa, 'إلغاء الفعالية: '.$cancellation->reason);
                }
            }
        } catch (Throwable $exception) {
            $failure = $exception;
        }

        $booking->refresh();
        if ($booking->state instanceof PendingReview || $booking->state instanceof Approved) {
            $booking->forceFill([
                'cancellation_reason' => $cancellation->reason,
            ])->save();
            $booking->state->transitionTo(Cancelled::class);
        }

        $booking->installments()
            ->where('state', BookingInstallmentState::Pending->value)
            ->update(['state' => BookingInstallmentState::Voided->value]);

        $releaseBookingSeats->execute($booking, ignoreActiveCheckoutSessions: true);

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    /** @return Builder<Payment> */
    private function eventPayments(EventCancellation $cancellation): Builder
    {
        return Payment::query()->whereHas(
            'bookingInstallment.paymentSchedule.booking',
            fn ($query) => $query->where('event_id', $cancellation->event_id),
        );
    }

    /** @return Builder<Payment> */
    private function capturedPayments(EventCancellation $cancellation): Builder
    {
        return $this->eventPayments($cancellation)
            ->whereIn('state', [PaymentState::Paid->value, PaymentState::PartiallyRefunded->value])
            ->with('refunds');
    }

    /** @return Builder<Payment> */
    private function bookingPayments(Booking $booking): Builder
    {
        return Payment::query()->whereHas(
            'bookingInstallment.paymentSchedule',
            fn ($query) => $query->where('booking_id', $booking->id),
        );
    }
}
