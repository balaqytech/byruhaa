<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\MinorProfileOrderNotifier;
use App\Modules\Identity\Data\MinorOrderStatusData;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Notifications\MinorOrderStatusChangedNotification;

class MinorProfileOrderNotifierService implements MinorProfileOrderNotifier
{
    public function notify(int $profileId, MinorOrderStatusData $order): void
    {
        $profile = MinorProfile::query()->find($profileId);

        if ($profile instanceof MinorProfile) {
            $profile->notify(new MinorOrderStatusChangedNotification($order));
        }
    }
}
