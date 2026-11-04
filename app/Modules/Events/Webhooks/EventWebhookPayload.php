<?php

namespace App\Modules\Events\Webhooks;

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\EventInterest;
use Carbon\CarbonInterface;

class EventWebhookPayload
{
    /**
     * @return array<string, mixed>
     */
    public function interestPayload(EventInterest $interest, CarbonInterface $occurredAt): array
    {
        return [
            'event' => 'interest.created',
            'customer_phone' => $interest->customer->phone_number,
            'occurred_at' => $occurredAt->toJSON(),
            'data' => [
                'interest' => [
                    'id' => $interest->id,
                    'status' => $interest->status->value,
                    'source' => $interest->source->value,
                    'preferred_contact_channel' => $interest->preferred_contact_channel,
                    'source_reference' => $interest->source_reference,
                    'contact_consent_at' => $interest->contact_consent_at?->toJSON(),
                    'last_expressed_at' => $interest->last_expressed_at->toJSON(),
                    'created_at' => $interest->created_at->toJSON(),
                    'customer_panel_url' => route('customer.interests.index'),
                ],
                'event' => [
                    'id' => $interest->event->id,
                    'name' => $interest->event->name,
                    'slug' => $interest->event->slug,
                    'public_url' => route('events.show', $interest->event),
                ],
                'customer' => [
                    'id' => $interest->customer->id,
                    'name' => $interest->customer->name,
                    'phone' => $interest->customer->phone_number,
                    'email' => $interest->customer->email,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bookingContractsPayload(Booking $booking): array
    {
        $contracts = $booking->familyMembers
            ->map(fn (BookingFamilyMember $bookingFamilyMember) => $bookingFamilyMember->contract)
            ->filter();
        $signedContracts = $contracts
            ->filter(fn ($contract): bool => $contract->signed_at !== null);
        $latestSignedAt = $signedContracts
            ->map(fn ($contract) => $contract->signed_at)
            ->filter()
            ->sort()
            ->last();

        return [
            'total_count' => $contracts->count(),
            'signed_count' => $signedContracts->count(),
            'all_signed' => $contracts->isNotEmpty() && $contracts->count() === $signedContracts->count(),
            'latest_signed_at' => $latestSignedAt?->toJSON(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bookingPayload(string $eventName, Booking $booking, CarbonInterface $occurredAt): array
    {
        return [
            'event' => $eventName,
            'customer_phone' => $booking->customer?->phone_number,
            'occurred_at' => $occurredAt->toJSON(),
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'status' => $booking->state->getValue(),
                    ...($eventName === 'booking.cancelled' ? ['reason' => $booking->cancellation_reason, 'cancellation_id' => $booking->event?->cancellation?->id] : []),
                    'customer_panel_url' => route('customer.bookings.show', $booking),
                ],
                'event' => [
                    'id' => $booking->event?->id,
                    'name' => $booking->event?->name,
                ],
                'customer' => [
                    'id' => $booking->customer?->id,
                    'name' => $booking->customer?->name,
                    'phone' => $booking->customer?->phone_number,
                    'email' => $booking->customer?->email,
                ],
                'participants' => $booking->familyMembers
                    ->map(fn (BookingFamilyMember $bookingFamilyMember): array => [
                        'booking_family_member_id' => $bookingFamilyMember->id,
                        'family_member_id' => $bookingFamilyMember->family_member_id,
                        'name' => $bookingFamilyMember->familyMember?->name,
                        'contract_id' => $bookingFamilyMember->contract?->id,
                        'contract_status' => $bookingFamilyMember->contract?->state?->getValue(),
                        'contract_signed_at' => $bookingFamilyMember->contract?->signed_at?->toJSON(),
                    ])
                    ->values()
                    ->all(),
                'pricing' => [
                    'unit_price_baisa' => $booking->unit_price_baisa,
                    'subtotal_baisa' => $booking->subtotal_baisa,
                    'discount_source' => $booking->discountSource(),
                    'coupon_code' => $booking->coupon_code,
                    'discount_amount_baisa' => $booking->discount_amount_baisa,
                    'total_baisa' => $booking->total_baisa,
                    'currency' => $booking->currency,
                ],
            ],
        ];
    }
}
