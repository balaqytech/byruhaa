<?php

namespace App\States\Booking;

class Approved extends BookingState
{
    public static string $name = 'approved';

    public function label(): string
    {
        return __('admin.statuses.approved');
    }
}
