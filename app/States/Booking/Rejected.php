<?php

namespace App\States\Booking;

class Rejected extends BookingState
{
    public static string $name = 'rejected';

    public function getLabel(): string
    {
        return __('admin.statuses.rejected');
    }

    public function getColor(): string
    {
        return 'danger';
    }
}
