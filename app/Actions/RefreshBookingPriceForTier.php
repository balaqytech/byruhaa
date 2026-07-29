<?php

namespace App\Actions;

use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\EventContract;
use App\Models\EventPriceTier;
use App\Services\ContractRenderer;
use App\States\Contract\Voided;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Illuminate\Validation\ValidationException;

class RefreshBookingPriceForTier
{
    public function __construct(private ContractRenderer $contractRenderer) {}

    public function execute(Booking $booking, ?EventPriceTier $tier): bool
    {
        $unitPriceBaisa = $tier instanceof EventPriceTier ? $tier->price_baisa : $booking->event->price_baisa;

        if ($booking->unit_price_baisa === $unitPriceBaisa) {
            return false;
        }

        if ($this->hasCapturedPayment($booking)) {
            throw ValidationException::withMessages([
                'payment' => __('ui.messages.paid_booking_price_cannot_change'),
            ]);
        }

        $familyMemberCount = max(1, $booking->familyMembers()->count());
        $subtotal = MoneyFactory::fromMinor($unitPriceBaisa * $familyMemberCount, $booking->currency);
        $discountAmount = $this->discountAmount($booking, $subtotal);
        $total = Money::max(MoneyFactory::zero($booking->currency), $subtotal->minus($discountAmount));

        $booking->forceFill([
            'unit_price_baisa' => $unitPriceBaisa,
            'family_member_count' => $familyMemberCount,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total' => $total,
        ])->save();

        $this->refreshPaymentSchedule($booking);

        return $this->replaceCurrentContracts($booking);
    }

    private function hasCapturedPayment(Booking $booking): bool
    {
        return $booking->installments()
            ->whereHas('payments', fn ($query) => $query->whereIn('state', [
                PaymentState::Paid->value,
                PaymentState::PartiallyRefunded->value,
            ]))
            ->exists();
    }

    private function discountAmount(Booking $booking, Money $subtotal): Money
    {
        return MoneyFactory::fromMinor(
            min($booking->discount_amount_baisa, MoneyFactory::toMinor($subtotal)),
            $booking->currency,
        );
    }

    private function refreshPaymentSchedule(Booking $booking): void
    {
        $schedule = $booking->paymentSchedule()->with('installments')->first();

        if ($schedule === null) {
            return;
        }

        $schedule->forceFill([
            'subtotal' => $booking->subtotal,
            'discount_amount' => $booking->discount_amount,
            'total' => $booking->total,
        ])->save();

        $installments = $schedule->installments->values();
        $percentages = $installments->pluck('percentage')->all();
        $grossAmounts = array_reverse($booking->subtotal->allocate(...array_reverse($percentages)));
        $discountAmounts = array_reverse($booking->discount_amount->allocate(...array_reverse($percentages)));

        foreach ($installments as $index => $installment) {
            $grossAmount = $grossAmounts[$index];
            $discountAmount = $discountAmounts[$index];

            $installment->forceFill([
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'amount' => $grossAmount->minus($discountAmount),
            ])->save();
        }
    }

    private function replaceCurrentContracts(Booking $booking): bool
    {
        $booking->loadMissing('familyMembers.contract');
        $requiresNewSignatures = $booking->familyMembers->isNotEmpty();

        foreach ($booking->familyMembers as $bookingFamilyMember) {
            $contract = $bookingFamilyMember->contract;

            if ($contract instanceof EventContract) {
                if (! $contract->state instanceof Voided) {
                    $contract->state->transitionTo(Voided::class);
                }

                $contract->forceFill(['superseded_at' => now()])->save();
                $bookingFamilyMember->unsetRelation('contract');
            }

            $bookingFamilyMember->contracts()->create([
                'contract_html' => $this->contractRenderer->html($bookingFamilyMember),
            ]);
        }

        return $requiresNewSignatures;
    }
}
