<?php

namespace App\Modules\Affiliates\Actions;

use App\Enums\AffiliateStatus;
use App\Enums\PaymentState;
use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Finance\Models\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostAffiliateCommissionForPayment
{
    public function __construct(private PostAffiliateCommissionLedgerTransaction $postLedgerTransaction) {}

    public function execute(Payment $payment): ?AffiliateCommission
    {
        $commission = DB::transaction(function () use ($payment): ?AffiliateCommission {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->with('bookingInstallment.paymentSchedule.booking.affiliateReferral.affiliate')
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->state !== PaymentState::Paid) {
                throw new RuntimeException('Only paid payments can earn affiliate commission.');
            }

            $booking = $payment->bookingInstallment->paymentSchedule->booking;

            $booking->newQuery()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingCommission = AffiliateCommission::query()
                ->where('booking_id', $booking->id)
                ->first();

            if ($existingCommission instanceof AffiliateCommission) {
                return $existingCommission;
            }

            $referral = $booking->affiliateReferral;

            if (! $referral || $referral->affiliate->status !== AffiliateStatus::Approved) {
                return null;
            }

            $commissionAmountBaisa = (int) config('affiliate.commission_amount_baisa', 30000);

            if ($commissionAmountBaisa <= 0) {
                return null;
            }

            return AffiliateCommission::query()->create([
                'affiliate_id' => $referral->affiliate_id,
                'affiliate_referral_id' => $referral->id,
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'base_amount_baisa' => $booking->total_baisa,
                'commission_rate_basis_points' => 0,
                'commission_amount_baisa' => $commissionAmountBaisa,
                'currency' => $payment->currency,
                'earned_at' => $payment->paid_at ?? now(),
            ]);
        });

        if ($commission instanceof AffiliateCommission) {
            $this->postLedgerTransaction->execute($commission);
        }

        return $commission;
    }
}
