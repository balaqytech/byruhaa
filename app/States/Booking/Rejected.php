<?php

namespace App\States\Booking;

class Rejected extends BookingState
{
    public static string $name = 'rejected';

    public function label(): string
    {
        return 'Rejected';
    }
}
