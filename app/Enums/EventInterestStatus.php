<?php

namespace App\Enums;

enum EventInterestStatus: string
{
    case Interested = 'interested';
    case BookingStarted = 'booking_started';
    case Converted = 'converted';
    case Withdrawn = 'withdrawn';
}
