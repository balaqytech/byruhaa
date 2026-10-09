<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Confirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmCashOrder
{
    public function __construct(
        private ConsumeInventoryReservation $consumeReservation,
        private ChangeOrderState $changeOrderState,
    ) {}

    public function execute(Order $order, int $cashReceivedBaisa, ?int $actorUserId): Order
    {
        return DB::transaction(function () use ($order, $cashReceivedBaisa, $actorUserId): Order {
            $order = Order::query()
                ->with('inventoryReservation.reservation')
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->payment_method !== 'cash' || $actorUserId === null || $order->pos_cashier_user_id !== $actorUserId) {
                throw ValidationException::withMessages(['payment' => 'هذا الطلب غير متاح للدفع النقدي من الكاشير.']);
            }

            if ($cashReceivedBaisa < $order->total_baisa) {
                throw ValidationException::withMessages(['cashReceived' => 'المبلغ المستلم أقل من إجمالي الطلب.']);
            }

            if ($order->status->getValue() !== OrderStatus::PendingPayment->value) {
                if (in_array($order->status->getValue(), [OrderStatus::Confirmed->value, OrderStatus::Accepted->value, OrderStatus::Preparing->value, OrderStatus::ReadyForPickup->value, OrderStatus::Completed->value], true)
                    && $order->cash_received_baisa === $cashReceivedBaisa) {
                    return $order;
                }

                throw ValidationException::withMessages(['order' => 'هذا الطلب لم يعد بانتظار الدفع.']);
            }

            $reservation = $order->inventoryReservation?->reservation;

            if ($reservation !== null && ($reservation->status !== InventoryReservationStatus::Pending || $reservation->expires_at->isPast())) {
                throw ValidationException::withMessages(['inventory' => 'المخزون المحجوز لم يعد متاحًا.']);
            }

            if ($reservation !== null && $this->consumeReservation->execute($reservation)->status !== InventoryReservationStatus::Consumed) {
                throw ValidationException::withMessages(['inventory' => 'تعذّر خصم المخزون المحجوز.']);
            }

            $order->forceFill([
                'cash_received_baisa' => $cashReceivedBaisa,
                'cash_change_baisa' => $cashReceivedBaisa - $order->total_baisa,
                'paid_at' => now(),
                'payment_reference' => 'CASH-'.$order->reference,
            ])->save();

            return $this->changeOrderState->execute($order, Confirmed::class, $actorUserId, 'Cash payment received at the POS.');
        });
    }
}
