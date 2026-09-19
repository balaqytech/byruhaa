<?php

namespace App\Modules\Store\Http\Controllers;

use App\Modules\Store\Actions\ConfirmWalletOrder;
use App\Modules\Store\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletOrderController
{
    public function confirmPage(Request $request, Order $order): View
    {
        $this->assertCustomerOwnsOrder($request, $order);
        abort_unless($order->payment_method === 'wallet', 404);
        abort_unless(config('byruhaa.wallets.enabled', false), 404);

        return view('pages.public.site.store.wallet-confirm', [
            'order' => $order->load(['items', 'statusHistory']),
        ]);
    }

    public function confirm(Request $request, Order $order, ConfirmWalletOrder $confirmWalletOrder): RedirectResponse
    {
        $this->assertCustomerOwnsOrder($request, $order);
        abort_unless($order->payment_method === 'wallet' && $order->customer_id !== null, 404);
        $customerId = (int) $request->user('customer')->getAuthIdentifier();

        try {
            $confirmWalletOrder->execute($order, $customerId, (int) $order->minor_profile_id);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'تم تأكيد الدفع من محفظة الابن.');
    }

    private function assertCustomerOwnsOrder(Request $request, Order $order): void
    {
        abort_unless((int) $order->customer_id === (int) $request->user('customer')->getAuthIdentifier(), 404);
    }
}
