<?php

namespace App\Enums;

enum SeatAllocationState: string
{
    case Held = 'held';
    case Reserved = 'reserved';
    case Released = 'released';
}
