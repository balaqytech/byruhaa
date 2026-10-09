<?php

namespace App\Modules\Pos\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Order;

class CashierOrderAccess
{
    public function canReprint(User $cashier, Order $order): bool
    {
        if (! $cashier->can('Sell:Pos') || $order->pos_cashier_user_id === null || $order->paid_at === null) {
            return false;
        }

        if ($order->pos_cashier_user_id === $cashier->id) {
            return true;
        }

        return $cashier->can('Reprint:PosReceipts') && $order->created_at?->isSameDay(today()) === true;
    }
}
