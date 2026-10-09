<?php

namespace App\Modules\Pos\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Pos\Services\CashierOrderAccess;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Services\StoreReceiptRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CashierOrderReceiptController
{
    public function show(Order $order, StoreReceiptRenderer $renderer, CashierOrderAccess $access): Response
    {
        $cashier = Auth::guard('cashier')->user();

        abort_unless($cashier instanceof User && $access->canReprint($cashier, $order), 404);
        abort_unless($renderer->isAvailable($order), 404);

        return response()->view('pages.customer.store.orders.receipt', [
            'order' => $order->load(['items', 'statusHistory']),
            'receiptSettings' => $renderer->settingsFor($order),
            'forPdf' => false,
            'forCashier' => true,
        ]);
    }
}
