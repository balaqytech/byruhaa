<?php

namespace App\States\Booking;

class Cancelled extends BookingState
{
    public static string $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }
}
