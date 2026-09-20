<?php

namespace App\Modules\Store\Actions;

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Identity\Contracts\MinorProfilePurchasing;
use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Confirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmWalletOrder
{
    public function __construct(
        private WalletService $wallets,
        private MinorProfilePurchasing $minorProfiles,
        private ConsumeInventoryReservation $consumeReservation,
        private ChangeOrderState $changeOrderState,
    ) {}

    public function execute(Order $order, int $customerId, ?int $minorProfileId = null): Order
    {
        return DB::transaction(function () use ($order, $customerId, $minorProfileId): Order {
            $order = Order::query()
                ->with('inventoryReservation.reservation')
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $order->customer_id !== $customerId || $order->payment_method !== 'wallet') {
                throw ValidationException::withMessages(['order' => 'This order cannot be paid from the wallet.']);
            }

            if ($order->minor_profile_id === null || $minorProfileId !== (int) $order->minor_profile_id) {
                throw ValidationException::withMessages(['order' => 'The child account does not match this order.']);
            }

            $minorProfile = $this->minorProfiles->forGuardian($minorProfileId, $customerId);
            if (! $minorProfile->walletSpendingEnabled || $minorProfile->requiresGuardianPhoneVerification()) {
                throw ValidationException::withMessages(['payment' => 'Wallet spending is not currently authorized.']);
            }

            if ($order->status->getValue() !== OrderStatus::PendingPayment->value) {
                if (in_array($order->status->getValue(), [OrderStatus::Confirmed->value, OrderStatus::Accepted->value, OrderStatus::Preparing->value, OrderStatus::ReadyForPickup->value, OrderStatus::Completed->value], true)) {
                    return $order;
                }

                throw ValidationException::withMessages(['order' => 'This order is not awaiting payment.']);
            }

            $reservation = $order->inventoryReservation?->reservation;
            if ($reservation !== null && ($reservation->status !== InventoryReservationStatus::Pending || $reservation->expires_at->isPast())) {
                throw ValidationException::withMessages(['order' => 'The inventory reservation is no longer available.']);
            }

            if ($reservation !== null && $this->consumeReservation->execute($reservation)->status !== InventoryReservationStatus::Consumed) {
                throw ValidationException::withMessages(['inventory' => 'Reserved stock could not be consumed.']);
            }

            $spend = $this->wallets->spend($minorProfileId, $order->reference, (int) $order->total_baisa, $order->currency);
            $order->forceFill([
                'paid_at' => now(),
                'payment_reference' => 'WALLET-'.$spend->movementId,
            ])->save();

            return $this->changeOrderState->execute($order, Confirmed::class, note: 'Wallet payment confirmed.');
        });
    }
}
