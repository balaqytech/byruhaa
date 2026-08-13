<?php

namespace App\Modules\Store\Actions;

use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Data\Payments\PaymentVerificationData;
use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Confirmed;
use App\Modules\Store\States\Order\Expired;
use App\Modules\Store\States\Order\Refunded;
use App\Modules\Store\States\Order\RefundPending;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ConfirmStorePayment
{
    public function __construct(
        private ConsumeInventoryReservation $consumeReservation,
        private ChangeOrderState $changeOrderState,
        private PaymentService $payments,
    ) {}

    public function execute(PaymentVerificationData $payment): void
    {
        if ($payment->subjectType !== 'store_order' || $payment->status !== 'paid') {
            return;
        }

        $order = Order::query()->where('reference', $payment->subjectReference)->firstOrFail();
        if ($order->total_baisa !== $payment->amountBaisa || $order->currency !== $payment->currency) {
            throw ValidationException::withMessages(['payment' => 'The payment amount or currency does not match the order.']);
        }

        if ($order->paid_at === null || $order->payment_reference === null) {
            $order->forceFill([
                'paid_at' => Carbon::now(),
                'payment_reference' => $payment->paymentReference,
                'provider_invoice' => $payment->providerInvoice,
            ])->save();
        }

        $status = $order->status->getValue();
        if (in_array($status, [OrderStatus::Confirmed->value, OrderStatus::Accepted->value, OrderStatus::Preparing->value, OrderStatus::ReadyForPickup->value, OrderStatus::Completed->value], true)) {
            if ($order->paid_at === null || $order->payment_reference === null) {
                $order->forceFill([
                    'paid_at' => $order->paid_at ?? Carbon::now(),
                    'payment_reference' => $order->payment_reference ?? $payment->paymentReference,
                    'provider_invoice' => $order->provider_invoice ?? $payment->providerInvoice,
                ])->save();
            }

            return;
        }

        if ($status === OrderStatus::PendingPayment->value) {
            $reservation = $order->inventoryReservation()->with('reservation')->first()?->reservation;

            if ($reservation?->status === InventoryReservationStatus::Pending) {
                $reservation = $this->consumeReservation->execute($reservation);
            }

            $order = Order::query()->whereKey($order->getKey())->firstOrFail();
            if ($order->status->getValue() === OrderStatus::PendingPayment->value && (! $reservation || $reservation->status === InventoryReservationStatus::Consumed)) {
                $this->changeOrderState->execute($order, Confirmed::class, note: 'Store payment confirmed.');

                return;
            }

            if ($order->status->getValue() === OrderStatus::PendingPayment->value) {
                $this->changeOrderState->execute($order, Expired::class, note: 'Payment arrived after the inventory reservation expired.');
            }
        }

        $order = Order::query()->whereKey($order->getKey())->firstOrFail();
        if ($order->status->getValue() === OrderStatus::Expired->value) {
            $this->changeOrderState->execute($order, RefundPending::class, note: 'Payment requires a refund because the order expired.');
        }

        $order = Order::query()->whereKey($order->getKey())->firstOrFail();
        if ($order->status->getValue() !== OrderStatus::RefundPending->value) {
            return;
        }

        $refund = $this->payments->refundPayment($payment->paymentReference, $payment->amountBaisa, 'Store order expired before payment confirmation.');
        if ($refund->status === 'succeeded') {
            $this->changeOrderState->execute($order, Refunded::class, note: 'Late Store payment refunded.');
        }
    }
}
