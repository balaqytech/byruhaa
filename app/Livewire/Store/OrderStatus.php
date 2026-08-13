<?php

namespace App\Livewire\Store;

use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\OrderState;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderStatus extends Component
{
    public string $paymentToken;

    public function mount(string $paymentToken): void
    {
        $this->paymentToken = $paymentToken;
    }

    public function refreshStatus(): void {}

    public function render(): View
    {
        $order = Order::query()
            ->where('payment_token', $this->paymentToken)
            ->with(['items', 'statusHistory'])
            ->firstOrFail();

        return view('livewire.store.order-status', [
            'order' => $order,
            'isPending' => $order->status->getValue() === 'pending_payment',
            'statusLabel' => $this->statusLabel($order->status),
        ]);
    }

    private function statusLabel(OrderState $status): string
    {
        return match ($status->getValue()) {
            'pending_payment' => 'بانتظار الدفع',
            'confirmed' => 'تم تأكيد الطلب',
            'accepted' => 'تم قبول الطلب',
            'preparing' => 'جارٍ تجهيز الطلب',
            'ready_for_pickup' => 'الطلب جاهز للاستلام',
            'completed' => 'اكتمل الطلب',
            'rejected' => 'تعذر قبول الطلب',
            'cancelled' => 'أُلغي الطلب',
            'expired' => 'انتهت مهلة الدفع',
            'refund_pending' => 'جارٍ تجهيز الاسترداد',
            'refunded' => 'تم استرداد المبلغ',
            default => 'حالة الطلب',
        };
    }
}
