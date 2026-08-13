<?php

use App\Modules\Store\Actions\InitiateStorePayment;
use App\Modules\Store\Actions\ReorderStoreOrder;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Services\StoreReceiptRenderer;
use Flux\Flux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('تفاصيل طلب القهوة')] class extends Component {
    public Order $order;

    public bool $retrying = false;

    public bool $reordering = false;

    public ?string $feedback = null;

    public ?string $error = null;

    public function mount(Order $order): void
    {
        $this->order = $this->ownedOrder($order->payment_token);
    }

    public function refreshOrder(): void
    {
        $this->order = $this->ownedOrder($this->order->payment_token);
    }

    public function retryPayment(InitiateStorePayment $initiatePayment): RedirectResponse
    {
        $this->retrying = true;
        $this->error = null;

        $rateLimitKey = 'store-order-payment-retry:'.Auth::guard('customer')->id().':'.$this->order->getKey();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $this->error = 'أعد المحاولة بعد قليل.';
            $this->retrying = false;

            return redirect()->route('customer.store.orders.show', $this->order->payment_token);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $payment = $initiatePayment->execute($this->ownedOrder($this->order->payment_token), (int) Auth::guard('customer')->id());

            if (blank($payment->checkoutUrl)) {
                $this->refreshOrder();

                return redirect()->route('customer.store.orders.show', $this->order->payment_token);
            }

            return redirect()->away($payment->checkoutUrl);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error = 'تعذر بدء الدفع الآن. حاول مرة أخرى بعد قليل.';

            return redirect()->route('customer.store.orders.show', $this->order->payment_token);
        } finally {
            $this->retrying = false;
        }
    }

    public function reorder(ReorderStoreOrder $reorderStoreOrder): ?RedirectResponse
    {
        $this->reordering = true;
        $this->feedback = null;
        $this->error = null;

        try {
            $result = $reorderStoreOrder->execute($this->ownedOrder($this->order->payment_token), (int) Auth::guard('customer')->id());

            if ($result['added'] === []) {
                $this->error = 'لا توجد أصناف متاحة حاليًا لإضافتها إلى السلة.';

                return null;
            }

            session(['store_cart_token' => $result['cart']?->token]);
            $this->feedback = 'أضيفت '.count($result['added']).' أصناف إلى سلتك. راجعها قبل الإرسال.';

            return redirect()->route('coffee');
        } finally {
            $this->reordering = false;
        }
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_payment' => 'بانتظار الدفع', 'confirmed' => 'تم تأكيد الطلب', 'accepted' => 'تم قبول الطلب',
            'preparing' => 'جارٍ تجهيز الطلب', 'ready_for_pickup' => 'الطلب جاهز للاستلام', 'completed' => 'اكتمل الطلب',
            'rejected' => 'تعذر قبول الطلب', 'cancelled' => 'أُلغي الطلب', 'expired' => 'انتهت مهلة الدفع',
            'refund_pending' => 'جارٍ تجهيز الاسترداد', 'refunded' => 'تم استرداد المبلغ', default => 'حالة الطلب',
        };
    }

    public function isPolling(): bool
    {
        return in_array($this->order->status->getValue(), [
            OrderStatus::PendingPayment->value, OrderStatus::Confirmed->value, OrderStatus::Accepted->value,
            OrderStatus::Preparing->value, OrderStatus::ReadyForPickup->value,
        ], true);
    }

    public function canReorder(): bool
    {
        return in_array($this->order->status->getValue(), [
            OrderStatus::Confirmed->value, OrderStatus::Accepted->value, OrderStatus::Preparing->value,
            OrderStatus::ReadyForPickup->value, OrderStatus::Completed->value, OrderStatus::Refunded->value,
        ], true);
    }

    public function with(StoreReceiptRenderer $receiptRenderer): array
    {
        return ['order' => $this->order->load(['items', 'statusHistory']), 'receiptAvailable' => $receiptRenderer->isAvailable($this->order)];
    }

    private function ownedOrder(string $paymentToken): Order
    {
        return Order::query()->where('payment_token', $paymentToken)->where('customer_id', Auth::guard('customer')->id())->with(['items', 'statusHistory'])->firstOrFail();
    }
}; ?>

<section class="flex flex-col gap-6" dir="rtl" @if ($this->isPolling()) wire:poll.15s="refreshOrder" @endif>
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:heading size="xl">تفاصيل الطلب</flux:heading>
                <flux:badge color="emerald">{{ $this->statusLabel($order->status->getValue()) }}</flux:badge>
            </div>
            <flux:subheading class="mt-2">{{ $order->reference }} · <span dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</span></flux:subheading>
        </div>
        <flux:button :href="route('customer.store.orders.index')" wire:navigate variant="outline"><x-hugeicon name="arrow-left-02" class="text-lg" /> طلباتي</flux:button>
    </div>

    @if ($error)<div class="rounded-xl border border-rose-300/40 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-300/10 dark:text-rose-100">{{ $error }}</div>@endif
    @if ($feedback)<div class="rounded-xl border border-emerald-300/40 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-100">{{ $feedback }}</div>@endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5"><flux:text>العميل</flux:text><flux:heading class="mt-1 text-base">{{ $order->customer_name }}</flux:heading><flux:text dir="ltr">{{ $order->customer_phone }}</flux:text>@if ($order->recipient_name)<flux:text class="mt-2">المستلم: {{ $order->recipient_name }} · {{ $order->recipient_phone }}</flux:text>@endif</div>
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5"><flux:text>طريقة الاستلام</flux:text><flux:heading class="mt-1 text-base">{{ $order->pickup_type === 'scheduled' ? 'استلام مجدول' : 'استلام فوري' }}</flux:heading>@if ($order->pickup_at)<flux:text dir="ltr">{{ $order->pickup_at->format('Y-m-d H:i') }}</flux:text>@endif</div>
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5"><flux:text>الإجمالي</flux:text><flux:heading class="mt-1 text-2xl"><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></flux:heading></div>
    </div>

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        <flux:heading>الأصناف</flux:heading>
        <div class="mt-4 divide-y divide-emerald-900/10 dark:divide-white/10">
            @foreach ($order->items as $item)
                <div wire:key="order-item-{{ $item->id }}" class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><flux:heading class="text-base">{{ $item->product_name }} · {{ $item->option_name }}</flux:heading><flux:text>SKU: {{ $item->sku }} · الكمية: {{ $item->quantity }}</flux:text><flux:text>ضريبة الصنف: <x-money :amount-baisa="$item->vat_baisa" :currency="$item->currency" /></flux:text>@if ($item->note)<flux:text>ملاحظة: {{ $item->note }}</flux:text>@endif</div>
                    <div class="text-start"><flux:text><x-money :amount-baisa="$item->unit_price_baisa" :currency="$item->currency" /> × {{ $item->quantity }}</flux:text><flux:heading class="text-base"><x-money :amount-baisa="$item->line_total_baisa" :currency="$item->currency" /></flux:heading></div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 grid gap-2 border-t border-emerald-900/10 pt-4 text-sm dark:border-white/10 sm:max-w-sm sm:ms-auto"><div class="flex justify-between"><span>المجموع قبل الضريبة</span><span><x-money :amount-baisa="$order->subtotal_baisa" :currency="$order->currency" /></span></div><div class="flex justify-between"><span>الضريبة ({{ $order->vat_rate_percentage ?? 5 }}٪)</span><span><x-money :amount-baisa="$order->vat_baisa" :currency="$order->currency" /></span></div><div class="flex justify-between text-base font-bold"><span>الإجمالي</span><span><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></span></div></div>
    </div>

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5"><flux:heading>تتبع الحالة</flux:heading><div class="mt-4 space-y-4">@foreach ($order->statusHistory as $history)<div wire:key="status-history-{{ $history->id }}" class="flex gap-3"><span class="mt-1 size-2 shrink-0 rounded-full bg-emerald-600"></span><div><flux:heading class="text-sm">{{ $this->statusLabel($history->to_status) }}</flux:heading><flux:text>{{ $history->note }}</flux:text><flux:text dir="ltr">{{ $history->created_at->format('Y-m-d H:i') }}</flux:text></div></div>@endforeach</div></div>

    <div class="flex flex-wrap gap-3">
        @if ($order->status->getValue() === OrderStatus::PendingPayment->value)
            <flux:button wire:click="retryPayment" wire:target="retryPayment" wire:loading.attr="disabled" variant="primary"><x-hugeicon name="wallet-02" class="text-lg" /> ادفع الآن</flux:button>
        @endif
        @if ($receiptAvailable)
            <flux:button :href="route('customer.store.orders.receipt', $order->payment_token)" target="_blank" variant="outline"><x-hugeicon name="file-view" class="text-lg" /> طباعة الفاتورة</flux:button>
            <flux:button :href="route('customer.store.orders.receipt.pdf', $order->payment_token)" variant="outline"><x-hugeicon name="download-01" class="text-lg" /> تحميل PDF</flux:button>
        @endif
        @if ($this->canReorder())
            <flux:button wire:click="reorder" wire:target="reorder" wire:loading.attr="disabled" variant="primary"><x-hugeicon name="reload" class="text-lg" /> إعادة الطلب</flux:button>
        @endif
        <flux:button :href="route('coffee')" wire:navigate variant="ghost">العودة إلى القهوة</flux:button>
    </div>
</section>
