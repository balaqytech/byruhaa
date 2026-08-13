<?php

namespace App\Modules\Store\Enums;

enum InventoryReservationStatus: string
{
    case Pending = 'pending';
    case Consumed = 'consumed';
    case Released = 'released';
    case Expired = 'expired';
}
