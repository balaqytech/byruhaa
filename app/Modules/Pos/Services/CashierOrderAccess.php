<?php

namespace App\Modules\Pos\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use Illuminate\Support\Carbon;

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

    public function canViewCashRefund(User $cashier, Order $order): bool
    {
        return $cashier->can('Sell:Pos')
            && $cashier->can('RefundCash:Pos')
            && $order->pos_cashier_user_id !== null
            && $order->payment_method === 'cash'
            && $order->paid_at !== null;
    }

    public function canRefund(User $cashier, Order $order): bool
    {
        if (! $this->canViewCashRefund($cashier, $order)) {
            return false;
        }

        $completedAt = $order->statusHistory()->where('to_status', OrderStatus::Completed->value)->value('created_at');
        if ($completedAt !== null && now()->greaterThan(Carbon::parse($completedAt)->addHours(24))) {
            return false;
        }

        $reserved = (int) $order->refundRequests()
            ->whereIn('status', ['pending', 'approved', 'processing', 'manual_review', 'failed', 'paid'])
            ->sum('amount_baisa');

        return $reserved < $order->total_baisa;
    }
}
