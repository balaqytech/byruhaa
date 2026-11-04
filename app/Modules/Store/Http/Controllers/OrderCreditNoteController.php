<?php

namespace App\Modules\Store\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Pos\Services\CashierOrderAccess;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderRefundRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OrderCreditNoteController
{
    public function customer(Order $order, OrderRefundRequest $refund): Response
    {
        abort_unless((int) $order->customer_id === (int) Auth::guard('customer')->id(), 404);

        return $this->render($order, $refund);
    }

    public function staff(Order $order, OrderRefundRequest $refund): Response
    {
        abort_unless(Auth::guard('web')->user()?->isPanelAdministrator() || Auth::guard('web')->user()?->can('View:OrderRefundRequest'), 403);

        return $this->render($order, $refund);
    }

    public function cashier(Order $order, OrderRefundRequest $refund, CashierOrderAccess $access): Response
    {
        $cashier = Auth::guard('cashier')->user();
        abort_unless($cashier instanceof User && $access->canViewCashRefund($cashier, $order), 404);

        return $this->render($order, $refund);
    }

    private function render(Order $order, OrderRefundRequest $refund): Response
    {
        abort_unless($refund->order_id === $order->id && $refund->status === 'paid', 404);

        return response()->view('pages.customer.store.orders.credit-note', [
            'order' => $order,
            'refund' => $refund->load('lines'),
        ]);
    }
}
