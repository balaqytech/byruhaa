<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Contracts\MinorProfileOrderNotifier;
use App\Modules\Identity\Data\MinorOrderStatusData;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Notifications\MinorOrderStatusChangedNotification;
use Illuminate\Support\Facades\Cache;

class MinorProfileOrderNotifierService implements MinorProfileOrderNotifier
{
    public function notify(int $profileId, MinorOrderStatusData $order): void
    {
        $profile = MinorProfile::query()->find($profileId);

        $deduplicationKey = "minor-order-notification:{$profileId}:{$order->reference}:{$order->status}";
        $deduplicationWindow = now()->addHours((int) config('byruhaa.minor_accounts.browser_notifications.duplicate_window_hours', 24));

        if ($profile instanceof MinorProfile && Cache::add($deduplicationKey, true, $deduplicationWindow)) {
            $profile->notify(new MinorOrderStatusChangedNotification($order));
        }
    }
}
