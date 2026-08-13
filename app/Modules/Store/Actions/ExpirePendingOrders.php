<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Settings\StoreSettings;
use App\Modules\Store\States\Order\Expired;
use Illuminate\Support\Facades\DB;

class ExpirePendingOrders
{
    public function __construct(private ChangeOrderState $changeOrderState, private ReleaseInventoryReservation $releaseReservation, private StoreSettings $settings) {}

    public function execute(): int
    {
        $expired = 0;
        Order::query()
            ->where('status', OrderStatus::PendingPayment->value)
            ->where('created_at', '<=', now()->subMinutes(max(1, $this->settings->reservation_duration_minutes)))
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$expired): void {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$expired): void {
                        $order = Order::query()->with('inventoryReservation.reservation')->whereKey($order->id)->lockForUpdate()->first();

                        if (! $order || $order->status->getValue() !== OrderStatus::PendingPayment->value) {
                            return;
                        }

                        $this->changeOrderState->execute($order, Expired::class);
                        if ($order->inventoryReservation?->reservation) {
                            $this->releaseReservation->execute($order->inventoryReservation->reservation);
                        }
                        $expired++;
                    });
                }
            });

        return $expired;
    }
}
