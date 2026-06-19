<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'event_id' => $this->event_id,
            'reference' => $this->reference,
            'state' => $this->state->getValue(),
            'state_label' => $this->state->label(),
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'reviewed_at' => $this->reviewed_at?->toJSON(),
            'review_notes' => $this->review_notes,
            'unit_price_baisa' => $this->unit_price_baisa,
            'currency' => $this->currency,
            'family_member_count' => $this->family_member_count,
            'subtotal_baisa' => $this->subtotal_baisa,
            'discount_id' => $this->discount_id,
            'discount_name' => $this->discount_name,
            'discount_amount_baisa' => $this->discount_amount_baisa,
            'total_baisa' => $this->total_baisa,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
            'event' => EventResource::make($this->whenLoaded('event')),
            'family_members' => $this->whenLoaded('familyMembers', fn () => $this->familyMembers->map(
                fn (BookingFamilyMember $bookingFamilyMember): array => [
                    'booking_family_member_id' => $bookingFamilyMember->id,
                    'id' => $bookingFamilyMember->family_member_id,
                    'name' => $bookingFamilyMember->familyMember?->name,
                    'birth_date' => $bookingFamilyMember->familyMember?->birth_date?->toDateString(),
                ],
            )->values()),
            'payment_schedule' => $this->whenLoaded('paymentSchedule', fn (): ?array => $this->paymentSchedule ? [
                'id' => $this->paymentSchedule->id,
                'event_payment_plan_id' => $this->paymentSchedule->event_payment_plan_id,
                'plan_name' => $this->paymentSchedule->plan_name,
                'currency' => $this->paymentSchedule->currency,
                'subtotal_baisa' => $this->paymentSchedule->subtotal_baisa,
                'discount_amount_baisa' => $this->paymentSchedule->discount_amount_baisa,
                'total_baisa' => $this->paymentSchedule->total_baisa,
                'installments' => $this->paymentSchedule->relationLoaded('installments')
                    ? $this->paymentSchedule->installments->map(
                        fn (BookingInstallment $installment): array => [
                            'id' => $installment->id,
                            'name' => $installment->name,
                            'sequence' => $installment->sequence,
                            'percentage' => $installment->percentage,
                            'due_date' => $installment->due_date?->toDateString(),
                            'gross_amount_baisa' => $installment->gross_amount_baisa,
                            'discount_amount_baisa' => $installment->discount_amount_baisa,
                            'amount_baisa' => $installment->amount_baisa,
                            'state' => $installment->state->value,
                            'paid_at' => $installment->paid_at?->toJSON(),
                        ],
                    )->values()
                    : null,
            ] : null),
        ];
    }
}
