<?php

namespace App\Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Actions\ManageOrderRefund;
use App\Modules\Store\Http\Requests\OrderRefundFormRequest;
use App\Modules\Store\Models\Order;
use App\Support\Money\MoneyFactory;
use Illuminate\Http\RedirectResponse;

class OrderRefundController extends Controller
{
    public function store(OrderRefundFormRequest $request, Order $order, ManageOrderRefund $refunds): RedirectResponse
    {
        $data = $request->validated();
        $amount = filled($data['amount_omr'] ?? null) ? MoneyFactory::omrStringToBaisa((string) $data['amount_omr']) : 0;
        $refunds->request(
            $order,
            (int) $request->user('customer')->getAuthIdentifier(),
            null,
            $amount,
            (string) $data['reason'],
            (string) $data['operation_key'],
            array_map('intval', array_filter($data['items'] ?? [], fn ($quantity): bool => filled($quantity))),
        );

        return back()->with('refund_request_status', 'pending');
    }
}
