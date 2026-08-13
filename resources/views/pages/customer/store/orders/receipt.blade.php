@php($forPdf = $forPdf ?? false)
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة ضريبية - {{ $order->reference }}</title>
    @if (! $forPdf)
        @vite(['resources/css/app.css'])
        <style> @media print { .receipt-actions { display: none !important; } body { background: white !important; } } </style>
    @endif
</head>
<body class="bg-zinc-100 p-4 text-zinc-900 sm:p-8">
    @unless ($forPdf)
        <div class="receipt-actions mx-auto mb-4 flex max-w-3xl justify-end gap-2">
            <button type="button" onclick="window.print()" class="rounded-lg bg-emerald-800 px-4 py-2 text-sm font-semibold text-white">طباعة</button>
            <a href="{{ route('customer.store.orders.receipt.pdf', $order->payment_token) }}" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold">تحميل PDF</a>
        </div>
    @endunless
    <main class="mx-auto max-w-3xl rounded-xl bg-white p-6 shadow-sm sm:p-10">
        <header class="flex flex-col justify-between gap-5 border-b border-zinc-200 pb-6 sm:flex-row sm:items-start">
            <div>
                @if ($receiptSettings['seller_legal_name'])<h1 class="text-2xl font-bold">{{ $receiptSettings['seller_legal_name'] }}</h1>@endif
                @if ($receiptSettings['seller_tax_number'])<p class="mt-1 text-sm">الرقم الضريبي: {{ $receiptSettings['seller_tax_number'] }}</p>@endif
                @if ($receiptSettings['seller_address'])<p class="mt-1 text-sm">{{ $receiptSettings['seller_address'] }}</p>@endif
                @if ($receiptSettings['seller_phone'])<p class="mt-1 text-sm" dir="ltr">{{ $receiptSettings['seller_phone'] }}</p>@endif
            </div>
            <div class="text-start">
                <h2 class="text-xl font-bold">فاتورة ضريبية</h2>
                <p class="mt-2 text-sm">رقم الفاتورة: <span dir="ltr">{{ $order->reference }}</span></p>
                <p class="mt-1 text-sm">تاريخ الطلب: <span dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</span></p>
                @if ($order->paid_at)<p class="mt-1 text-sm">تاريخ الدفع: <span dir="ltr">{{ $order->paid_at->format('Y-m-d H:i') }}</span></p>@endif
            </div>
        </header>
        <section class="mt-6 grid gap-4 rounded-lg bg-zinc-50 p-4 text-sm sm:grid-cols-2">
            <div><strong>العميل:</strong> {{ $order->customer_name }}<br><span dir="ltr">{{ $order->customer_phone }}</span></div>
            <div><strong>الاستلام:</strong> {{ $order->pickup_type === 'scheduled' ? 'مجدول' : 'فوري' }}@if ($order->pickup_at)<br><span dir="ltr">{{ $order->pickup_at->format('Y-m-d H:i') }}</span>@endif</div>
        </section>
        <table class="mt-6 w-full border-collapse text-sm">
            <thead><tr class="border-b border-zinc-300 text-start"><th class="p-2 text-start">الصنف</th><th class="p-2 text-start">SKU</th><th class="p-2 text-start">الكمية</th><th class="p-2 text-start">السعر</th><th class="p-2 text-start">الإجمالي</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr class="border-b border-zinc-100"><td class="p-2">{{ $item->product_name }} · {{ $item->option_name }}</td><td class="p-2" dir="ltr">{{ $item->sku }}</td><td class="p-2">{{ $item->quantity }}</td><td class="p-2"><x-money :amount-baisa="$item->unit_price_baisa" :currency="$item->currency" /></td><td class="p-2"><x-money :amount-baisa="$item->line_total_baisa" :currency="$item->currency" /></td></tr>
                @endforeach
            </tbody>
        </table>
        <dl class="mt-6 ms-auto grid max-w-sm gap-2 text-sm"><div class="flex justify-between"><dt>المجموع قبل الضريبة</dt><dd><x-money :amount-baisa="$order->subtotal_baisa" :currency="$order->currency" /></dd></div><div class="flex justify-between"><dt>الضريبة ({{ $receiptSettings['vat_rate_percentage'] }}٪)</dt><dd><x-money :amount-baisa="$order->vat_baisa" :currency="$order->currency" /></dd></div><div class="flex justify-between border-t border-zinc-300 pt-2 text-base font-bold"><dt>الإجمالي</dt><dd><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></dd></div></dl>
        @if ($order->payment_reference || $order->provider_invoice)<div class="mt-6 border-t border-zinc-200 pt-4 text-sm">@if ($order->payment_reference)<p>مرجع الدفع: <span dir="ltr">{{ $order->payment_reference }}</span></p>@endif @if ($order->provider_invoice)<p class="mt-1">فاتورة مزود الدفع: <span dir="ltr">{{ $order->provider_invoice }}</span></p>@endif</div>@endif
        @if (in_array($order->status->getValue(), ['refund_pending', 'refunded'], true))<p class="mt-6 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">حالة الاسترداد: {{ $order->status->getValue() === 'refunded' ? 'تم استرداد المبلغ' : 'قيد الاسترداد' }}</p>@endif
        @if ($receiptSettings['receipt_footer'])<footer class="mt-8 border-t border-zinc-200 pt-4 text-center text-sm text-zinc-600">{{ $receiptSettings['receipt_footer'] }}</footer>@endif
    </main>
</body>
</html>
