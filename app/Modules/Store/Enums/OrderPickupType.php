<?php

namespace App\Modules\Store\Enums;

enum OrderPickupType: string
{
    case Immediate = 'immediate';
    case Scheduled = 'scheduled';
}
