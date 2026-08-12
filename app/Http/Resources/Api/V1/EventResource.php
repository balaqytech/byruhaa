<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\FormatsApiMoney;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Models\EventPaymentPlanInstallment;
use App\Modules\Events\Models\EventPriceTier;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
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
            'enrollment_status' => $this->enrollment_status->value,
            'enrollment_status_label' => $this->enrollment_status->getLabel(),
            'can_express_interest' => $this->canExpressInterest(),
            'can_book' => $this->canBook(),
            'primary_action' => match (true) {
                $this->canBook() => ['type' => 'book', 'label' => 'احجز الآن'],
                $this->canExpressInterest() => ['type' => 'express_interest', 'label' => 'أبدِ اهتمامك'],
                default => null,
            },
            'excerpt' => $this->excerpt,
            'location' => $this->location,
            'starts_at' => $this->starts_at?->toJSON(),
            'ends_at' => $this->ends_at?->toJSON(),
            'minimum_age' => $this->minimum_age,
            'maximum_age' => $this->maximum_age,
            'seat_capacity' => $this->seat_capacity,
            'remaining_seats' => $this->remainingSeats(),
            'price' => $this->money($this->price),
            'currency' => $this->currency,
            'price_tiers' => $this->whenLoaded(
                'priceTiers',
                fn (): array => $this->resource instanceof Event ? $this->priceTiersForApi($this->resource) : [],
            ),
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
            )->values()->all()),
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
            )->values()->all()),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, position: int, seat_capacity: int, remaining_seats: int, price: string|null, currency: string}>
     */
    private function priceTiersForApi(Event $event): array
    {
        $priceTiers = $event->priceTiers
            ->filter(fn (EventPriceTier $tier): bool => $tier->price_baisa <= $event->price_baisa)
            ->values();

        return $priceTiers->map(fn (EventPriceTier $tier): array => [
            'id' => $tier->id,
            'name' => $tier->name,
            'position' => $tier->position,
            'seat_capacity' => $tier->seat_capacity,
            'remaining_seats' => max(0, $tier->seat_capacity - $tier->usedSeatsCount()),
            'price' => $this->money($tier->price),
            'currency' => $tier->currency,
        ])->all();
    }

    /**
     * @return array<int, array{id: int, name: string|null, sequence: int, percentage: int, due_date: string, amount: string|null, currency: string}>
     */
    private function paymentPlanInstallments(EventPaymentPlan $paymentPlan): array
    {
        $amounts = $this->paymentPlanInstallmentAmounts($paymentPlan);

        return $paymentPlan->installments->values()->map(
            fn (EventPaymentPlanInstallment $installment, int $index): array => [
                'id' => $installment->id,
                'name' => $installment->name,
                'sequence' => $installment->sequence,
                'percentage' => $installment->percentage,
                'due_date' => $installment->due_date->toDateString(),
                'amount' => $this->money($amounts[$index] ?? null),
                'currency' => $this->currency,
            ],
        )->all();
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
