<?php

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\Data\MinorOrderStatusData;

interface MinorProfileOrderNotifier
{
    public function notify(int $profileId, MinorOrderStatusData $order): void;
}
