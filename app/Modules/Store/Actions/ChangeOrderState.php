<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Events\OrderStateChanged;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\OrderState;
use App\Modules\Store\States\Order\Preparing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class ChangeOrderState
{
    public function execute(Order $order, string $targetState, ?int $actorUserId = null, ?string $note = null): Order
    {
        if (! is_a($targetState, OrderState::class, true)) {
            throw ValidationException::withMessages(['status' => 'This order state is invalid.']);
        }

        return DB::transaction(function () use ($order, $targetState, $actorUserId, $note): Order {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $current = $order->status->getValue();

            if ($current === 'confirmed' && $order->pickup_type === 'scheduled' && $targetState === Preparing::class) {
                throw ValidationException::withMessages(['status' => 'Scheduled orders must be accepted before preparation.']);
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

            $refreshed = $order->refresh();

            OrderStateChanged::dispatch($refreshed, $current);

            return $refreshed;
        });
    }
}
