@php
    $cardPdf = request()->query('format') === 'card-pdf';
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بطاقة القائد — {{ $memberCode }}</title>
    @vite('resources/css/app.css')
    <style>
        :root { color-scheme: light; --green: #06382e; --deep: #032a23; --gold: #e9c469; --cream: #fbf5e7; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e8e7df; color: var(--deep); font-family: 'Thmanyah Sans', sans-serif; }
        .sheet { max-width: 760px; margin: auto; padding: 24px 14px 40px; }
        .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 22px; }
        .toolbar h1, .toolbar p { margin: 0; }
        .toolbar h1 { font: 900 25px 'Thmanyah Serif Display', sans-serif; }
        .toolbar p { font-size: 13px; }
        .print { border: 0; border-radius: 8px; padding: 10px 16px; background: var(--green); color: white; cursor: pointer; }
        .pair { display: grid; gap: 24px; justify-items: center; }
        .face { width: 100%; container-type: inline-size; }
        .face-label { display: block; margin-bottom: 6px; font-size: 12px; font-weight: 700; }
        .card { position: relative; isolation: isolate; overflow: hidden; aspect-ratio: 85.6 / 54; border-radius: 3.4cqw; box-shadow: 0 12px 30px #173a2a26; line-height: 1.25; }
        .card::after { position: absolute; inset: 1.5cqw; border: .23cqw solid var(--gold); border-radius: 2.6cqw; content: ''; pointer-events: none; }
        .front { display: grid; grid-template-columns: 30% minmax(0, 1fr) 29%; background: radial-gradient(ellipse at 5% 100%, #e8d9b1, transparent 55%), var(--cream); }
        .photo { display: grid; grid-template-rows: 1fr auto; gap: 1.4cqw; min-height: 0; padding: 6cqw 3cqw 3.7cqw 0; }
        .portrait { display: grid; place-items: center; min-height: 0; overflow: hidden; border: .43cqw solid #b78930; border-radius: 4cqw; background: #e3d8b9; font-size: 8cqw; font-weight: 900; }
        .portrait img { width: 100%; height: 100%; min-height: 0; object-fit: contain; }
        .code { font: 700 3cqw ui-monospace, monospace; letter-spacing: .04em; }
        .identity { display: flex; flex-direction: column; align-items: stretch; justify-content: center; gap: 1.45cqw; min-width: 0; padding: 3.2cqw 2cqw; }
        .brand { display: flex; align-items: center; justify-content: center; gap: 1.3cqw; }
        .brand img { width: 8cqw; height: 8cqw; object-fit: contain; }
        .brand strong { font: 900 4cqw/.95 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; }
        .brand small { display: block; font: 700 2cqw/1.2 'Thmanyah Sans', sans-serif; }
        .tagline { text-align: center; font-size: 2cqw; font-weight: 700; }
        .kicker { align-self: center; border-radius: 1.2cqw; padding: .5cqw 2cqw; background: #d9ae52; font-size: 2.45cqw; font-weight: 700; }
        .person-label, .phone-label { color: #5d5848; font-size: 2cqw; }
        .person-name { font: 900 3.65cqw/1.19 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; overflow-wrap: anywhere; }
        .person-name.long { font-size: 2.85cqw; }
        .guardian { border-top: .2cqw solid #b38c3f; padding-top: 1cqw; }
        .guardian b { display: block; font-size: 2.75cqw; text-align: right; }
        .scan { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.7cqw; border-inline-start: .65cqw solid var(--gold); border-radius: 0 13% 27% 0 / 0 36% 55% 0; background: linear-gradient(140deg, var(--green), var(--deep)); color: var(--cream); padding: 2cqw; text-align: center; }
        .scan strong { font-size: 2.65cqw; }
        .scan small { color: #f1dfac; font-size: 1.85cqw; }
        .qr { display: grid; width: 22cqw; aspect-ratio: 1; padding: 1.1cqw; border-radius: 1.5cqw; background: #fff; }
        .qr svg { display: block; width: 100%; height: 100%; }
        .back { display: grid; grid-template-rows: auto auto 1fr auto; gap: 1.5cqw; padding: 4.2cqw 6cqw; background: radial-gradient(ellipse at 7% 120%, #174d3b 0 35%, #a787483b 35.2%, transparent 35.6%), linear-gradient(125deg, var(--green), var(--deep)); color: var(--cream); }
        .back-top { display: flex; justify-content: space-between; align-items: start; }
        .back .qr { width: 14cqw; padding: .6cqw; border-radius: .6cqw; }
        .back h2 { margin: 1cqw 0 0; color: var(--gold); font: 900 4.6cqw/1.15 'Thmanyah Serif Display', sans-serif; }
        .back p { margin: 0; font-size: 2.65cqw; line-height: 1.45; }
        .contacts { align-self: center; width: 86%; margin-inline: auto; }
        .contacts h3 { margin: 0 0 .9cqw; color: var(--gold); text-align: center; font-size: 2.55cqw; }
        .contact { display: grid; grid-template-columns: 1.2fr 1fr; align-items: center; margin-top: .65cqw; border: .19cqw solid var(--gold); border-radius: 1.25cqw; padding: .55cqw 1.65cqw; font-size: 2.35cqw; }
        .contact b { border-inline-start: .18cqw solid var(--gold); text-align: center; }
        .back footer { display: flex; justify-content: space-between; align-items: end; font-size: 2.35cqw; }
        @page {
            @if ($cardPdf)
                size: 85.6mm 54mm;
                margin: 0;
            @else
                size: A4 portrait;
                margin: 14mm;
            @endif
        }
        @media print {
            body { background: white; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .sheet { max-width: none; padding: 0; }
            .toolbar, .face-label { display: none; }
            .pair { gap: 14mm; }
            .face { width: 85.6mm; break-inside: avoid; }
            .card { height: 54mm; box-shadow: none; }
            @if ($cardPdf)
                .pair { display: block; }
                .face + .face { break-before: page; }
            @endif
        }
    </style>
</head>
<body>
    @php
        $brandEmblem = asset('images/national-day-56/g-emblem.png');
        $supportPhone = '+'.ltrim((string) config('coffee.whatsapp_number'), '+');
    @endphp
    <main class="sheet">
        <header class="toolbar">
            <div><h1>بطاقة القائد</h1><p>وجها البطاقة بالمقاس الفعلي 85.6 × 54 مم</p></div>
            <div>
                @if ($cardPdf)
                    <button type="button" class="print" onclick="window.print()">حفظ PDF / طباعة</button>
                    <a class="print" href="{{ url()->current() }}">العودة إلى A4</a>
                    <p>اختر «حفظ بتنسيق PDF» في نافذة الطباعة، واضبط المقياس على ١٠٠٪.</p>
                @else
                    <button type="button" class="print" onclick="window.print()">طباعة A4</button>
                    <a class="print" href="{{ url()->current() }}?format=card-pdf">PDF للمطبعة · صفحتان</a>
                @endif
            </div>
        </header>
        <div class="pair">
            <section class="face" aria-label="الوجه الأمامي">
                <span class="face-label">الوجه الأمامي</span>
                <article class="card front">
                    <div class="photo">
                        <div class="portrait">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="صورة {{ $minorName }}">
                            @else
                                <span>{{ mb_substr($minorName, 0, 1) }}</span>
                            @endif
                        </div>
                        <strong class="code"><bdi dir="ltr">{{ $memberCode }}</bdi></strong>
                    </div>
                    <div class="identity">
                        <div class="brand"><img src="{{ $brandEmblem }}" alt=""><strong><small>قهوة</small>بيرحاء</strong></div>
                        <span class="tagline">قِسْ قياسك .. واستمتع</span>
                        <span class="kicker">بطاقة شراء القائد</span>
                        <div>
                            <div class="person-label">القائد</div>
                            <div class="person-name{{ mb_strlen($minorName) > 34 ? ' long' : '' }}">{{ $minorName }}</div>
                        </div>
                        <div class="guardian"><span class="phone-label">هاتف وليّ الأمر</span><b dir="ltr">{{ $guardianPhone ?: '—' }}</b></div>
                    </div>
                    <div class="scan">
                        <strong>امسح للتحقق</strong>
                        <div class="qr" role="img" aria-label="رمز QR لبطاقة الشراء">{!! $qrSvg !!}</div>
                        <small>للاستخدام عند نقطة البيع</small>
                    </div>
                </article>
            </section>
            <section class="face" aria-label="الوجه الخلفي">
                <span class="face-label">الوجه الخلفي</span>
                <article class="card back">
                    <div class="back-top">
                        <div>
                            <div class="brand"><img src="{{ $brandEmblem }}" alt=""><strong><small>قهوة</small>بيرحاء</strong></div>
                            <h2>بطاقتي معي</h2>
                        </div>
                        <div class="qr" role="img" aria-label="رمز QR لبطاقة الشراء">{!! $qrSvg !!}</div>
                    </div>
                    <div>
                        <p>استخدم هذه البطاقة للشراء من بيرحاء، واحتفظ بها في مكان آمن.</p>
                        <p>عند فقدانها، اطلب من وليّ الأمر إيقافها وإصدار بطاقة جديدة.</p>
                    </div>
                    <div class="contacts">
                        <h3>بيانات التواصل عند فقدان البطاقة</h3>
                        <div class="contact"><span>رقم وليّ الأمر</span><b dir="ltr">{{ $guardianPhone ?: '—' }}</b></div>
                        <div class="contact"><span>واتساب فعاليات بيرحاء</span><b dir="ltr">{{ $supportPhone }}</b></div>
                    </div>
                    <footer><span><bdi dir="ltr">BYRUHAA</bdi> · بطاقة القائد</span><strong class="code" dir="ltr">{{ $memberCode }}</strong></footer>
                </article>
            </section>
        </div>
    </main>
</body>
</html>
