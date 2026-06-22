<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\FormatsApiMoney;
use App\Models\Discount;
use App\Models\EventPaymentPlan;
use App\Models\EventPaymentPlanInstallment;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class EventResource extends JsonResource
{
    use FormatsApiMoney;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'excerpt' => $this->excerpt,
            'description_html' => $this->description_html,
            'contract_terms_html' => $this->contract_terms_html,
            'location' => $this->location,
            'starts_at' => $this->starts_at?->toJSON(),
            'ends_at' => $this->ends_at?->toJSON(),
            'minimum_age' => $this->minimum_age,
            'maximum_age' => $this->maximum_age,
            'seat_capacity' => $this->seat_capacity,
            'remaining_seats' => $this->remainingSeats(),
            'price' => $this->money($this->price),
            'currency' => $this->currency,
            'available_discounts' => $this->whenLoaded('availableDiscounts', fn () => $this->availableDiscounts->map(
                fn (Discount $discount): array => [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'type' => 'fixed_amount_per_family_member',
                    'value' => $this->money($discount->amount),
                    'currency' => $discount->currency,
                    'starts_at' => $discount->starts_at?->toJSON(),
                    'ends_at' => $discount->ends_at?->toJSON(),
                    'eligibility' => [
                        'minimum_family_members' => $discount->minimum_family_members,
                        'maximum_family_members' => $discount->maximum_family_members,
                    ],
                ],
            )->values()),
            'payment_plans' => $this->whenLoaded('paymentPlans', fn () => $this->paymentPlans->map(
                fn (EventPaymentPlan $paymentPlan): array => [
                    'id' => $paymentPlan->id,
                    'name' => $paymentPlan->name,
                    'installments_count' => $paymentPlan->relationLoaded('installments')
                        ? $paymentPlan->installments->count()
                        : 0,
                    'installments' => $paymentPlan->relationLoaded('installments')
                        ? $this->paymentPlanInstallments($paymentPlan)
                        : [],
                ],
            )->values()),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function paymentPlanInstallments(EventPaymentPlan $paymentPlan): Collection
    {
        $amounts = $this->paymentPlanInstallmentAmounts($paymentPlan);

        return $paymentPlan->installments->values()->map(
            fn (EventPaymentPlanInstallment $installment, int $index): array => [
                'id' => $installment->id,
                'name' => $installment->name,
                'sequence' => $installment->sequence,
                'percentage' => $installment->percentage,
                'due_date' => $installment->due_date?->toDateString(),
                'amount' => $this->money($amounts[$index] ?? null),
                'currency' => $this->currency,
            ],
        );
    }

    /**
     * @return array<int, Money>
     */
    private function paymentPlanInstallmentAmounts(EventPaymentPlan $paymentPlan): array
    {
        if ($paymentPlan->installments->isEmpty() || (int) $paymentPlan->installments->sum('percentage') !== 100) {
            return [];
        }

        $percentages = $paymentPlan->installments->pluck('percentage')->all();

        return array_reverse($this->price->allocate(...array_reverse($percentages)));
    }
}
