<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->payment_method === 'cash' && $order->pos_cashier_user_id !== null ? 'إيصال إرجاع نقدي' : 'إشعار دائن' }} {{ $refund->reference }}</title>
    <style>
        body { max-width: 760px; margin: 2rem auto; padding: 0 1.25rem; font-family: Tahoma, Arial, sans-serif; color: #19221d; line-height: 1.7; }
        header { border-bottom: 2px solid #28513c; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        h1 { margin-bottom: .25rem; font-size: 1.6rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: .7rem; border-bottom: 1px solid #d8dfd9; text-align: right; }
        th { background: #f2f6f2; }
        .total { font-weight: bold; font-size: 1.2rem; }
        .actions { margin: 1rem 0; }
        @media print { body { margin: 0; max-width: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <header>
        <h1>{{ $order->payment_method === 'cash' && $order->pos_cashier_user_id !== null ? 'إيصال إرجاع نقدي / Cash refund receipt' : 'إشعار دائن / Credit note' }}</h1>
        <div>{{ $order->seller_legal_name ?: 'بيرحاء' }}</div>
        @if ($order->seller_tax_number)
            <div>الرقم الضريبي: {{ $order->seller_tax_number }}</div>
        @endif
    </header>
    <div class="actions"><button type="button" onclick="window.print()">طباعة</button></div>
    <p>رقم إشعار التصحيح: <strong>{{ $refund->reference }}</strong><br>
        الفاتورة الأصلية / Original invoice: <strong>{{ $order->reference }}</strong><br>
        تاريخ رد المبلغ: {{ $refund->paid_at?->format('Y-m-d H:i') }}<br>
        سبب التصحيح: {{ $refund->reason }}</p>
    <table>
        <thead><tr><th>البيان</th><th>المبلغ OMR</th></tr></thead>
        <tbody>
            <tr><td>القيمة قبل VAT</td><td>{{ \App\Support\MoneyFormatter::baisa($refund->subtotal_baisa, $refund->currency) }}</td></tr>
            <tr><td>VAT المعكوسة</td><td>{{ \App\Support\MoneyFormatter::baisa($refund->vat_baisa, $refund->currency) }}</td></tr>
            <tr class="total"><td>إجمالي الاسترداد</td><td>{{ \App\Support\MoneyFormatter::baisa($refund->amount_baisa, $refund->currency) }}</td></tr>
        </tbody>
    </table>
    <p>وسيلة الاسترداد: {{ strtoupper($order->payment_method) }}<br>مرجع إثبات الرد: {{ $refund->payout_reference }}</p>
    <p>هذا الإشعار مرتبط بالفاتورة الأصلية أعلاه.</p>
</body>
</html>
