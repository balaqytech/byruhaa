<?php

namespace App\Modules\Events\States\Booking;

class PendingReview extends BookingState
{
    public static string $name = 'pending_review';

    public function getLabel(): string
    {
        return __('admin.statuses.pending_review');
    }

    public function getColor(): string
    {
        return 'warning';
    }
}
