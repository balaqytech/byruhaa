<?php

namespace App\Console\Commands;

use App\Enums\PaymentProvider;
use App\Enums\PaymentState;
use App\Modules\Finance\Actions\ConfirmThawaniPayment;
use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('payments:reconcile-thawani {--limit=100 : Maximum pending payments to reconcile}')]
#[Description('Reconcile pending Thawani checkout sessions with the gateway')]
class ReconcileThawaniPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ConfirmThawaniPayment $confirmThawaniPayment, PaymentService $paymentService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $payments = Payment::query()
            ->where('provider', PaymentProvider::Thawani->value)
            ->where('state', PaymentState::Pending->value)
            ->whereNotNull('provider_session_id')
            ->oldest()
            ->limit($limit)
            ->get();

        $failures = 0;

        foreach ($payments as $payment) {
            try {
                if ($payment->subject_type !== null) {
                    $paymentService->verifyPayment($payment->reference);
                } else {
                    $confirmThawaniPayment->confirm($payment);
                }
            } catch (Throwable $exception) {
                $failures++;
                $this->warn("Payment {$payment->reference} could not be reconciled: {$exception->getMessage()}");
            }
        }

        $this->info("Reconciled {$payments->count()} pending Thawani payment(s).");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
