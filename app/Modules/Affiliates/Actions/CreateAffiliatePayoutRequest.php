<?php

namespace App\Modules\Affiliates\Actions;

use App\Enums\AffiliateStatus;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAffiliatePayoutRequest
{
    /**
     * @param  array<string, mixed>|null  $paymentDetails
     */
    public function execute(Affiliate $affiliate, int $amountBaisa, ?string $affiliateNotes = null, ?array $paymentDetails = null): AffiliatePayoutRequest
    {
        return DB::transaction(function () use ($affiliate, $amountBaisa, $affiliateNotes, $paymentDetails): AffiliatePayoutRequest {
            $affiliate = Affiliate::query()
                ->whereKey($affiliate->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($affiliate->status !== AffiliateStatus::Approved) {
                throw ValidationException::withMessages([
                    'amount' => __('ui.affiliates.must_be_approved'),
                ]);
            }

            $minimumPayoutBaisa = (int) config('affiliate.minimum_payout_baisa', 20000);
            $availableBalanceBaisa = $affiliate->availableBalanceBaisa();

            if ($availableBalanceBaisa < $minimumPayoutBaisa || $amountBaisa < $minimumPayoutBaisa || $amountBaisa > $availableBalanceBaisa) {
                throw ValidationException::withMessages([
                    'amount' => __('ui.affiliates.payout_amount_invalid'),
                ]);
            }

            return $affiliate->payoutRequests()->create([
                'amount_baisa' => $amountBaisa,
                'currency' => 'OMR',
                'affiliate_notes' => $affiliateNotes,
                'payment_details' => $paymentDetails,
            ]);
        });
    }
}
