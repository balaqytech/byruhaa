<?php

namespace App\States\Booking;

class PendingReview extends BookingState
{
    public static string $name = 'pending_review';

    public function label(): string
    {
        return 'Pending review';
    }
}
