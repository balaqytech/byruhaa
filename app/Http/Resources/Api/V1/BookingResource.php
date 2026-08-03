<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\FormatsApiMoney;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\BookingInstallment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    use FormatsApiMoney;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Booking) {
            return [];
        }

        $booking = $this->resource;

        return [
            'id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'event_id' => $booking->event_id,
            'reference' => $booking->reference,
            'state' => $booking->state->getValue(),
            'state_label' => $booking->state->label(),
            'reviewed_by_user_id' => $booking->reviewed_by_user_id,
            'reviewed_at' => $booking->reviewed_at?->toJSON(),
            'review_notes' => $booking->review_notes,
            'unit_price' => $this->money($booking->unit_price),
            'currency' => $booking->currency,
            'family_member_count' => $booking->family_member_count,
            'subtotal' => $this->money($booking->subtotal),
            'discount_id' => $booking->discount_id,
            'coupon_id' => $booking->coupon_id,
            'coupon_code' => $booking->coupon_code,
            'discount_name' => $booking->discount_name,
            'discount_source' => $booking->discountSource(),
            'discount_amount' => $this->money($booking->discount_amount),
            'total' => $this->money($booking->total),
            'created_at' => $booking->created_at?->toJSON(),
            'updated_at' => $booking->updated_at?->toJSON(),
            'event' => EventResource::make($this->whenLoaded('event')),
            'family_members' => $this->whenLoaded('familyMembers', fn () => $booking->familyMembers->map(
                fn (BookingFamilyMember $bookingFamilyMember): array => [
                    'booking_family_member_id' => $bookingFamilyMember->id,
                    'id' => $bookingFamilyMember->family_member_id,
                    'name' => $bookingFamilyMember->familyMember?->name,
                    'birth_date' => $bookingFamilyMember->familyMember?->birth_date?->toDateString(),
                ],
            )->values()->all()),
            'payment_schedule' => $this->whenLoaded('paymentSchedule', fn (): ?array => $booking->paymentSchedule ? [
                'id' => $booking->paymentSchedule->id,
                'event_payment_plan_id' => $booking->paymentSchedule->event_payment_plan_id,
                'plan_name' => $booking->paymentSchedule->plan_name,
                'currency' => $booking->paymentSchedule->currency,
                'subtotal' => $this->money($booking->paymentSchedule->subtotal),
                'discount_amount' => $this->money($booking->paymentSchedule->discount_amount),
                'total' => $this->money($booking->paymentSchedule->total),
                'installments' => $booking->paymentSchedule->relationLoaded('installments')
                    ? $booking->paymentSchedule->installments->map(
                        fn (BookingInstallment $installment): array => [
                            'id' => $installment->id,
                            'name' => $installment->name,
                            'sequence' => $installment->sequence,
                            'percentage' => $installment->percentage,
                            'due_date' => $installment->due_date->toDateString(),
                            'gross_amount' => $this->money($installment->gross_amount),
                            'discount_amount' => $this->money($installment->discount_amount),
                            'amount' => $this->money($installment->amount),
                            'currency' => $installment->currency,
                            'state' => $installment->state->value,
                            'paid_at' => $installment->paid_at?->toJSON(),
                        ],
                    )->values()->all()
                    : null,
            ] : null),
        ];
    }
}
