<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 18mm 16mm 18mm 16mm;
            header: page-header;
            footer: page-footer;
        }

        body {
            direction: rtl;
            color: #17382f;
            font-family: xbriyaz, dejavusans, sans-serif;
            font-size: 13px;
            line-height: 1.85;
            text-align: right;
        }

        .document {
            border: 2px solid #14614f;
            padding: 13mm 11mm 11mm;
            position: relative;
        }

        .document:before {
            border: 1px solid #d6b75c;
            bottom: 3mm;
            content: "";
            left: 3mm;
            position: absolute;
            right: 3mm;
            top: 3mm;
        }

        .content {
            position: relative;
            z-index: 2;
        }

        .header-table,
        .meta-table,
        .signature-table {
            border-collapse: collapse;
            width: 100%;
        }

        .logo-cell {
            width: 34mm;
            vertical-align: top;
        }

        .logo {
            background: #14614f;
            border-radius: 8px;
            padding: 5mm 4mm;
            width: 28mm;
        }

        .title-cell {
            text-align: center;
            vertical-align: top;
        }

        h1,
        h2,
        .brand {
            font-family: lateef, xbriyaz, dejavusans, sans-serif;
            letter-spacing: 0;
        }

        .brand {
            color: #14614f;
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 2mm;
        }

        h1 {
            color: #17382f;
            font-size: 25px;
            font-weight: bold;
            line-height: 1.3;
            margin: 0;
        }

        .subtitle {
            color: #8a6f21;
            font-size: 12px;
            margin-top: 2mm;
        }

        .reference {
            border: 1px solid #d6b75c;
            color: #17382f;
            font-family: dejavusansmono, dejavusans, sans-serif;
            font-size: 11px;
            padding: 2mm 3mm;
            text-align: center;
            width: 34mm;
        }

        .divider {
            background: #d6b75c;
            height: 1px;
            margin: 7mm 0;
        }

        h2 {
            border-right: 5px solid #d6b75c;
            color: #14614f;
            font-size: 18px;
            font-weight: bold;
            line-height: 1.2;
            margin: 0 0 4mm;
            padding-right: 3mm;
        }

        .intro {
            background: #f7fbf8;
            border: 1px solid #dbe9e3;
            border-radius: 8px;
            margin-bottom: 6mm;
            padding: 4mm 5mm;
        }

        .meta-table th,
        .meta-table td {
            border: 1px solid #dbe9e3;
            padding: 3mm;
            vertical-align: top;
        }

        .meta-table th {
            background: #eef7f2;
            color: #14614f;
            font-weight: bold;
            width: 27%;
        }

        .meta-table td {
            background: #ffffff;
            color: #17382f;
        }

        .terms {
            color: #17382f;
            margin-top: 4mm;
        }

        .terms h1,
        .terms h2,
        .terms h3 {
            border: 0;
            color: #14614f;
            font-size: 16px;
            margin: 4mm 0 2mm;
            padding: 0;
        }

        .terms p {
            margin: 0 0 3mm;
        }

        .terms ul,
        .terms ol {
            margin: 2mm 7mm 3mm 0;
            padding: 0;
        }

        .terms li {
            margin-bottom: 1.5mm;
        }

        .signature-box {
            background: #fffdf6;
            border: 1px solid #d6b75c;
            margin-top: 8mm;
            page-break-inside: avoid;
            padding: 5mm;
        }

        .signature-table td {
            vertical-align: top;
            width: 50%;
        }

        .signature-image {
            border: 1px dashed #c2a64e;
            height: 31mm;
            margin-top: 2mm;
            padding: 2mm;
            text-align: center;
        }

        .signature-image img {
            max-height: 27mm;
            max-width: 72mm;
        }

        .seal {
            border: 2px solid #14614f;
            border-radius: 45px;
            color: #14614f;
            font-family: lateef, xbriyaz, dejavusans, sans-serif;
            font-size: 16px;
            height: 32mm;
            line-height: 1.35;
            margin-right: auto;
            margin-top: 2mm;
            padding-top: 9mm;
            text-align: center;
            width: 32mm;
        }

        .muted {
            color: #64776f;
        }

        .ltr {
            direction: ltr;
            font-family: dejavusansmono, dejavusans, sans-serif;
            unicode-bidi: embed;
        }
    </style>
</head>
<body>
    <htmlpageheader name="page-header">
        <div style="border-bottom: 1px solid #d6b75c; color: #64776f; font-size: 10px; padding-bottom: 2mm; text-align: center;">
            بيرحاء للفعاليات - نسخة إلكترونية معتمدة من عقد المشاركة
        </div>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <div style="border-top: 1px solid #d6b75c; color: #64776f; font-size: 10px; padding-top: 2mm; text-align: center;">
            صفحة {PAGENO} من {nbpg} - مرجع الحجز <span class="ltr">{{ $booking->reference }}</span>
        </div>
    </htmlpagefooter>

    <div class="document">
        <div class="content">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <img class="logo" src="{{ public_path('logo.png') }}" alt="بيرحاء">
                    </td>
                    <td class="title-cell">
                        <div class="brand">بيرحاء للفعاليات</div>
                        <h1>عقد مشاركة في فعالية</h1>
                        <div class="subtitle">{{ $event->name }}</div>
                    </td>
                    <td style="width: 38mm;">
                        <div class="reference">
                            مرجع الحجز<br>
                            <span class="ltr">{{ $booking->reference }}</span>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            <div class="intro">
                تم إعداد هذا العقد إلكترونياً بين بيرحاء للفعاليات وولي الأمر الموضح أدناه، وذلك لتنظيم مشاركة فرد العائلة في الفعالية المحددة، وفق الشروط والأحكام المثبتة في هذا المستند.
            </div>

            <h2>بيانات العقد</h2>
            <table class="meta-table">
                <tr>
                    <th>اسم الفعالية</th>
                    <td>{{ $event->name }}</td>
                    <th>نوع الفعالية</th>
                    <td>{{ trans()->has("admin.event_types.{$event->type}") ? __("admin.event_types.{$event->type}") : $event->type }}</td>
                </tr>
                <tr>
                    <th>ولي الأمر</th>
                    <td>{{ $customer->name }}</td>
                    <th>فرد العائلة</th>
                    <td>{{ $familyMember->name }}</td>
                </tr>
                <tr>
                    <th>تاريخ الفعالية</th>
                    <td><span class="ltr">{{ $event->starts_at?->format('Y-m-d H:i') ?? 'سيتم الإعلان عنه لاحقاً' }}</span></td>
                    <th>الموقع</th>
                    <td>{{ $event->location ?: 'سيتم الإعلان عنه لاحقاً' }}</td>
                </tr>
                <tr>
                    <th>تاريخ إصدار العقد</th>
                    <td><span class="ltr">{{ $contract->created_at->format('Y-m-d H:i') }}</span></td>
                    <th>حالة التوقيع</th>
                    <td>{{ $contract->state->label() }}</td>
                </tr>
            </table>

            <div class="divider"></div>

            <h2>الشروط والأحكام</h2>
            <div class="terms">
                {!! $contract->contract_html !!}
            </div>

            @if ($contract->signature_path)
                <div class="signature-box">
                    <h2>التوقيع الإلكتروني</h2>
                    <table class="signature-table">
                        <tr>
                            <td>
                                <p><strong>اسم الموقع:</strong> {{ $contract->signed_name }}</p>
                                <p><strong>تاريخ التوقيع:</strong> <span class="ltr">{{ $contract->signed_at?->format('Y-m-d H:i') }}</span></p>
                                <p><strong>عنوان الاتصال:</strong> <span class="ltr">{{ $contract->signed_ip ?: '-' }}</span></p>
                                <div class="signature-image">
                                    <img src="{{ storage_path('app/private/'.$contract->signature_path) }}" alt="التوقيع الإلكتروني">
                                </div>
                            </td>
                            <td>
                                <div class="seal">
                                    بيرحاء<br>
                                    للفعاليات<br>
                                    عقد معتمد
                                </div>
                                <p class="muted" style="margin-top: 5mm;">يعد هذا التوقيع موافقة إلكترونية من ولي الأمر على بيانات العقد وشروطه.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            @else
                <div class="signature-box">
                    <h2>حالة التوقيع</h2>
                    <p class="muted">لم يتم توقيع هذا العقد إلكترونياً بعد.</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
