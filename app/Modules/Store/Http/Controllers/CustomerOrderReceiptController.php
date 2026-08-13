<?php

namespace App\Modules\Store\Http\Controllers;

use App\Modules\Store\Models\Order;
use App\Modules\Store\Services\StoreReceiptRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerOrderReceiptController
{
    public function html(Order $order, StoreReceiptRenderer $renderer): Response
    {
        $order = $this->ownedOrder($order);
        abort_unless($renderer->isAvailable($order), 404);

        return response()->view('pages.customer.store.orders.receipt', [
            'order' => $order->load(['items', 'statusHistory']),
            'receiptSettings' => $renderer->settingsFor($order),
            'forPdf' => false,
        ]);
    }

    public function pdf(Order $order, StoreReceiptRenderer $renderer): StreamedResponse
    {
        $order = $this->ownedOrder($order)->load(['items', 'statusHistory']);
        abort_unless($renderer->isAvailable($order), 404);

        return response()->streamDownload(
            fn (): string => $renderer->pdf($order),
            'byruhaa-receipt-'.$order->reference.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function ownedOrder(Order $order): Order
    {
        return Order::query()
            ->whereKey($order->getKey())
            ->where('customer_id', Auth::guard('customer')->id())
            ->with(['items', 'statusHistory'])
            ->firstOrFail();
    }
}
