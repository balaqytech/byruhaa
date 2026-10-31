<?php

namespace App\Modules\Store\Actions;

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Events\OrderStateChanged;
use App\Modules\Store\Listeners\SendUchatOrderStateWebhook;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Cancelled;
use App\Modules\Store\States\Order\OrderState;
use App\Modules\Store\States\Order\Preparing;
use App\Modules\Store\States\Order\Refunded;
use App\Modules\Store\States\Order\Rejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class ChangeOrderState
{
    public function __construct(private WalletService $wallets, private PostStoreOrderCompletion $postCompletion, private SendUchatOrderStateWebhook $webhooks) {}

    public function execute(Order $order, string $targetState, ?int $actorUserId = null, ?string $note = null): Order
    {
        if (! is_a($targetState, OrderState::class, true)) {
            throw ValidationException::withMessages(['status' => 'This order state is invalid.']);
        }

        return DB::transaction(function () use ($order, $targetState, $actorUserId, $note): Order {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $current = $order->status->getValue();

            if ($targetState === Refunded::class
                || ($order->paid_at !== null && in_array($targetState, [Cancelled::class, Rejected::class], true))) {
                $refunded = (int) $order->refundRequests()->where('status', 'paid')->sum('amount_baisa');
                if ($order->paid_at === null || $refunded < $order->total_baisa) {
                    throw ValidationException::withMessages(['status' => 'The paid order cannot be cancelled or marked refunded before the full payout is confirmed.']);
                }
            }

            if ($order->pickup_type === 'scheduled' && $targetState === Preparing::class) {
                if ($current !== 'accepted') {
                    throw ValidationException::withMessages(['status' => 'Scheduled orders must be accepted before preparation.']);
                }

                if (! $order->canStartScheduledPreparation()) {
                    throw ValidationException::withMessages(['status' => 'Scheduled preparation is not open yet.']);
                }
            }

            try {
                $order->status->transitionTo($targetState);
            } catch (CouldNotPerformTransition) {
                throw ValidationException::withMessages(['status' => 'This order transition is not allowed.']);
            }

            $order->statusHistory()->create([
                'from_status' => $current,
                'to_status' => $order->status->getValue(),
                'actor_user_id' => $actorUserId,
                'note' => $note,
            ]);

            if ($order->payment_method === 'wallet' && $order->status->getValue() === OrderStatus::Completed->value) {
                $this->wallets->markPurchaseEligible($order->reference);
            }

            if ($order->status->getValue() === OrderStatus::Completed->value) {
                $this->postCompletion->execute($order);
            }

            $refreshed = $order->refresh();

            $this->webhooks->handle(new OrderStateChanged($refreshed, $current));
            OrderStateChanged::dispatch($refreshed, $current);

            return $refreshed;
        });
    }
}
