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
        $status = $order->status->getValue();

        if ($profileId === null || ! in_array($status, config('byruhaa.minor_accounts.browser_notifications.order_statuses', []), true)) {
            return;
        }

        $this->notifier->notify($profileId, new MinorOrderStatusData(
            $order->reference,
            $status,
            $order->status->label(),
            $order->total_baisa,
            $order->currency,
            route('minor.orders.show', $order->payment_token),
        ));
    }
}
