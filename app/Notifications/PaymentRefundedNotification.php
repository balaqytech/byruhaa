<?php

namespace App\Notifications;

use App\Modules\Finance\Models\PaymentRefund;
use App\Support\Money\MoneyFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $paymentRefundId)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $refund = PaymentRefund::query()
            ->with('payment.bookingInstallment.paymentSchedule.booking.event')
            ->findOrFail($this->paymentRefundId);
        $booking = $refund->payment->bookingInstallment->paymentSchedule->booking;

        return [
            'type' => 'payment_refunded',
            'title' => 'تم رد دفعتك',
            'message' => 'تم رد '.MoneyFactory::formatMinorUnits($refund->amount_baisa, $refund->currency)." {$refund->currency} لفعالية {$booking->event->name}.",
            'refund_id' => $refund->id,
            'booking_id' => $booking->id,
            'event_id' => $booking->event_id,
            'amount_baisa' => $refund->amount_baisa,
            'currency' => $refund->currency,
        ];
    }
}
