<?php

namespace App\Modules\Events\States\Booking;

class Approved extends BookingState
{
    public static string $name = 'approved';

    public function getLabel(): string
    {
        return __('admin.statuses.approved');
    }

    public function getColor(): string
    {
        return 'success';
    }
}
