<?php

namespace App\Modules\Events\States\Booking;

class Cancelled extends BookingState
{
    public static string $name = 'cancelled';

    public function getLabel(): string
    {
        return __('admin.statuses.cancelled');
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
