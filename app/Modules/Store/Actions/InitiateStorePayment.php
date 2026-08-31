<?php

namespace App\Modules\Store\Actions;

use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
use App\Modules\Finance\Data\Payments\PaymentCheckoutRequest;
use App\Modules\Identity\Contracts\MinorProfilePurchasing;
use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InitiateStorePayment
{
    public function __construct(private PaymentService $payments, private StoreSettings $settings, private MinorProfilePurchasing $minorProfiles) {}

    public function execute(Order $order, ?int $customerId = null, ?int $minorProfileId = null, bool $allowSignedLink = false): PaymentCheckoutData
    {
        $request = DB::transaction(function () use ($order, $customerId, $minorProfileId, $allowSignedLink): PaymentCheckoutRequest {
            $order = Order::query()
                ->with(['items', 'inventoryReservation.reservation'])
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->getRawOriginal('customer_id') !== null && $customerId !== null && (int) $order->getRawOriginal('customer_id') !== $customerId) {
                throw ValidationException::withMessages(['order' => 'This order does not belong to the authenticated customer.']);
            }

            if ($order->getRawOriginal('customer_id') !== null && $customerId === null && ! $allowSignedLink) {
                throw ValidationException::withMessages(['order' => 'Authentication or a signed payment link is required.']);
            }

            if ($minorProfileId !== null) {
                if ($customerId === null) {
                    throw ValidationException::withMessages(['payment' => 'A guardian account is required for direct payment.']);
                }

                $profile = $this->minorProfiles->forGuardian($minorProfileId, $customerId);

                if ((int) ($order->getRawOriginal('minor_profile_id') ?? 0) !== $minorProfileId) {
                    throw ValidationException::withMessages(['order' => 'This order does not belong to the authenticated minor profile.']);
                }

                if (! $profile->directPaymentEnabled) {
                    throw ValidationException::withMessages(['payment' => 'This order must be paid by the guardian.']);
                }
            }

            if ($order->status->getValue() !== OrderStatus::PendingPayment->value) {
                throw ValidationException::withMessages(['order' => 'This order is not awaiting payment.']);
            }

            if (! $this->settings->ordering_enabled) {
                throw ValidationException::withMessages(['ordering' => 'Store ordering is currently disabled.']);
            }

            $reservation = $order->inventoryReservation?->reservation;
            if ($reservation && $reservation->status !== InventoryReservationStatus::Pending) {
                throw ValidationException::withMessages(['order' => 'The inventory reservation is no longer available.']);
            }

            if ($reservation?->expires_at?->isPast()) {
                throw ValidationException::withMessages(['order' => 'The payment window for this order has expired.']);
            }

            $products = $order->items->map(fn ($item): array => [
                'name' => Str::limit(trim($item->product_name.' - '.$item->option_name), 40, ''),
                'quantity' => (int) $item->quantity,
                'unit_amount' => (int) $item->unit_price_baisa,
            ])->values()->all();

            $expiresAt = $reservation?->expires_at;
            $successUrl = URL::temporarySignedRoute('store.orders.payment.success', now()->addHours(12), ['order' => $order->payment_token]);
            $cancelUrl = URL::temporarySignedRoute('store.orders.payment.cancel', now()->addHours(12), ['order' => $order->payment_token]);

            return new PaymentCheckoutRequest(
                'store_order',
                $order->reference,
                (int) $order->total_baisa,
                $order->currency,
                $products,
                $successUrl,
                $cancelUrl,
                ['order_reference' => $order->reference],
                $expiresAt,
            );
        });

        return $this->payments->initiate($request);
    }
}
