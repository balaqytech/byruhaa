<?php

namespace App\Modules\Store\Listeners;

use App\Modules\Identity\Contracts\MinorProfileOrderNotifier;
use App\Modules\Identity\Data\MinorOrderStatusData;
use App\Modules\Store\Events\OrderStateChanged;

class NotifyMinorProfileOrderStatus
{
    public function __construct(private MinorProfileOrderNotifier $notifier) {}

    public function handle(OrderStateChanged $event): void
    {
        $order = $event->order;
        $profileId = $order->minor_profile_id;

        if ($profileId === null) {
            return;
        }

        $this->notifier->notify($profileId, new MinorOrderStatusData(
            $order->reference,
            $order->status->getValue(),
            $order->status->label(),
            route('minor.orders.show', $order->payment_token),
        ));
    }
}
