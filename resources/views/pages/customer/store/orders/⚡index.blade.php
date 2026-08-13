<?php

use App\Modules\Store\Models\Order;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Component;

new #[Title('طلبات القهوة')] class extends Component {
    use WithPagination;

    public int $customerId;

    public function mount(): void
    {
        $this->customerId = (int) auth('customer')->id();
    }

    public function with(): array
    {
        return [
            'orders' => Order::query()
                ->where('customer_id', $this->customerId)
                ->latest('created_at')
                ->latest('id')
                ->paginate(10),
        ];
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
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
}; ?>

<section class="flex flex-col gap-6" dir="rtl">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <flux:heading size="xl">طلبات القهوة</flux:heading>
            <flux:subheading>تابع طلباتك السابقة وافتح تفاصيل أي طلب.</flux:subheading>
        </div>

        <flux:button :href="route('coffee')" wire:navigate variant="outline">
            <x-hugeicon name="sparkles" class="text-lg" />
            اذهب إلى القهوة
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        @if ($orders->isEmpty())
            <div class="rounded-xl border border-dashed border-emerald-900/20 p-10 text-center dark:border-white/15">
                <flux:heading class="text-lg">لا توجد طلبات بعد</flux:heading>
                <flux:text class="mt-2">عندما تطلب من قهوة بيرحاء ستظهر طلباتك هنا.</flux:text>
                <flux:button class="mt-5" :href="route('coffee')" wire:navigate variant="primary">تصفح القهوة</flux:button>
            </div>
        @else
            <div class="space-y-3 md:hidden">
                @foreach ($orders as $order)
                    @php($status = $order->status->getValue())
                    <a wire:key="store-order-card-{{ $order->id }}" href="{{ route('customer.store.orders.show', $order->payment_token) }}" wire:navigate class="block rounded-xl border border-emerald-900/10 p-4 transition hover:border-emerald-500/40 dark:border-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <flux:heading class="text-base">{{ $order->reference }}</flux:heading>
                                <flux:text class="mt-1" dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</flux:text>
                            </div>
                            <flux:badge color="emerald">{{ $this->statusLabel($status) }}</flux:badge>
                        </div>
                        <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                            <span>{{ $order->pickup_type === 'scheduled' ? 'استلام مجدول' : 'استلام فوري' }}</span>
                            <span class="font-semibold"><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="hidden md:block">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>الطلب</flux:table.column>
                        <flux:table.column>التاريخ</flux:table.column>
                        <flux:table.column>الاستلام</flux:table.column>
                        <flux:table.column>الحالة</flux:table.column>
                        <flux:table.column>الإجمالي</flux:table.column>
                        <flux:table.column align="end">الإجراء</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($orders as $order)
                            @php($status = $order->status->getValue())
                            <flux:table.row wire:key="store-order-row-{{ $order->id }}">
                                <flux:table.cell variant="strong">{{ $order->reference }}</flux:table.cell>
                                <flux:table.cell dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</flux:table.cell>
                                <flux:table.cell>{{ $order->pickup_type === 'scheduled' ? 'مجدول · '.$order->pickup_at?->format('Y-m-d H:i') : 'فوري' }}</flux:table.cell>
                                <flux:table.cell><flux:badge color="emerald">{{ $this->statusLabel($status) }}</flux:badge></flux:table.cell>
                                <flux:table.cell><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></flux:table.cell>
                                <flux:table.cell align="end"><flux:button :href="route('customer.store.orders.show', $order->payment_token)" wire:navigate size="sm">فتح الطلب</flux:button></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="mt-5">{{ $orders->links() }}</div>
        @endif
    </div>
</section>
