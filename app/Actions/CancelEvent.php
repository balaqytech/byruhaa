<?php

namespace App\Actions;

use App\Enums\EventCancellationStatus;
use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentState;
use App\Jobs\ProcessEventCancellation;
use App\Models\Event;
use App\Models\EventCancellation;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CancelEvent
{
    public function execute(Event $event, string $reason, ?int $cancelledByUserId = null): EventCancellation
    {
        $cancellation = DB::transaction(function () use ($event, $reason, $cancelledByUserId): EventCancellation {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $existing = EventCancellation::query()->where('event_id', $event->id)->first();

            if ($existing instanceof EventCancellation) {
                return $existing;
            }

            $payments = Payment::query()
                ->whereHas('bookingInstallment.paymentSchedule.booking', fn ($query) => $query->where('event_id', $event->id))
                ->whereIn('state', [PaymentState::Paid->value, PaymentState::PartiallyRefunded->value])
                ->with('refunds')
                ->get();

            $cancellation = EventCancellation::query()->create([
                'event_id' => $event->id,
                'cancelled_by_user_id' => $cancelledByUserId,
                'status' => EventCancellationStatus::Pending,
                'reason' => trim($reason),
                'currency' => $event->currency,
                'bookings_count' => $event->bookings()->count(),
                'payments_count' => $payments->count(),
                'refundable_amount_baisa' => $payments->sum(fn (Payment $payment): int => $payment->refundableAmountBaisa()),
                'requested_at' => now(),
            ]);

            $event->forceFill([
                'status' => EventStatus::Cancelled,
                'enrollment_status' => EventEnrollmentStatus::BookingClosed,
            ])->save();

            DB::afterCommit(fn () => ProcessEventCancellation::dispatch($cancellation->id));

            return $cancellation;
        });

        return $cancellation->refresh();
    }
}
