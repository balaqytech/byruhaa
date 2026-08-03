<?php

namespace App\Actions;

use App\Enums\PaymentRefundState;
use App\Jobs\ProcessEventCancellation;
use App\Models\EventCancellation;
use App\Modules\Finance\Models\PaymentRefund;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmManualPaymentRefund
{
    public function __construct(private CompletePaymentRefund $completePaymentRefund) {}

    public function execute(PaymentRefund $refund, string $reference, CarbonInterface $completedAt, int $completedByUserId, ?string $notes = null, ?string $evidencePath = null): PaymentRefund
    {
        $refund = DB::transaction(function () use ($refund, $reference, $completedAt, $completedByUserId, $notes, $evidencePath): PaymentRefund {
            $refund = PaymentRefund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->state !== PaymentRefundState::ManualRequired) {
                throw ValidationException::withMessages(['refund' => 'هذا الاسترداد لا ينتظر معالجة يدوية.']);
            }

            $refund->forceFill([
                'resolution_method' => 'manual',
                'manual_reference' => trim($reference),
                'manual_notes' => $notes,
                'manual_evidence_path' => $evidencePath,
                'manually_completed_at' => $completedAt,
                'manually_completed_by_user_id' => $completedByUserId,
            ])->save();

            return $refund;
        });

        $refund = $this->completePaymentRefund->execute($refund, [
            'provider_refund_id' => $refund->manual_reference,
            'provider_status' => 'manual_completed',
            'processed_at' => $refund->manually_completed_at,
        ]);

        $refund->loadMissing('payment.bookingInstallment.paymentSchedule.booking.event');
        $event = $refund->payment->bookingInstallment->paymentSchedule->booking->event;
        $cancellation = EventCancellation::query()->whereBelongsTo($event)->first();
        if ($cancellation !== null) {
            ProcessEventCancellation::dispatch($cancellation->id);
        }

        return $refund;
    }
}
