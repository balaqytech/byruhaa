<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Services\Webhooks\ByruhaaWebhookSender;
use App\States\Booking\Approved;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingApprovalService
{
    public function __construct(
        private ContractRenderer $contractRenderer,
        private ByruhaaWebhookSender $webhookSender,
    ) {}

    public function approve(Booking $booking, ?User $reviewer = null, ?string $reviewNotes = null): Booking
    {
        $booking = DB::transaction(function () use ($booking, $reviewer, $reviewNotes): Booking {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->with(['event', 'customer', 'familyMembers.familyMember', 'familyMembers.contract'])
                ->lockForUpdate()
                ->firstOrFail();

            $requestedSeats = $booking->familyMembers->count();

            if ($requestedSeats < 1) {
                throw ValidationException::withMessages([
                    'booking' => __('ui.messages.booking_requires_family_member'),
                ]);
            }

            if ($booking->event->remainingSeats() < $requestedSeats) {
                throw ValidationException::withMessages([
                    'booking' => __('ui.messages.not_enough_seats'),
                ]);
            }

            $booking->forceFill([
                'reviewed_by_user_id' => $reviewer?->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ])->save();

            $booking->state->transitionTo(Approved::class);

            foreach ($booking->familyMembers as $bookingFamilyMember) {
                $bookingFamilyMember->contract()->firstOrCreate([], [
                    'contract_html' => $this->contractRenderer->html($bookingFamilyMember),
                ]);
            }

            return $booking->refresh();
        });

        $this->webhookSender->sendBookingApproved($booking);

        return $booking;
    }
}
