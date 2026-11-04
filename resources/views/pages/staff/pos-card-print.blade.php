<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بطاقة القائد — {{ $memberCode }}</title>
    @vite('resources/css/app.css')
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e8e7df; color: #193b2c; font-family: 'Thmanyah Sans', sans-serif; }
        .sheet { max-width: 720px; margin: 0 auto; padding: 32px 20px 48px; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 26px; }
        .toolbar h1 { margin: 0; font: 900 25px 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; }
        .toolbar p { margin: 4px 0 0; color: #657468; font-size: 13px; }
        .print { border: 0; border-radius: 12px; background: #19412f; color: white; padding: 13px 20px; font: 700 14px 'Thmanyah Sans', sans-serif; cursor: pointer; white-space: nowrap; }
        .pair { display: grid; gap: 28px; justify-items: center; }
        .face-wrap { width: 100%; }
        .face-label { display: block; margin: 0 0 8px; color: #54675a; font-size: 12px; font-weight: 700; }
        .card { position: relative; overflow: hidden; width: 100%; aspect-ratio: 85.6 / 54; border-radius: 17px; background: #fbf8ee; box-shadow: 0 16px 38px #173a2a24; }
        .card::before { position: absolute; inset: 8px; border: 1px solid #c5a95f7a; border-radius: 11px; content: ''; pointer-events: none; z-index: 3; }
        .front { display: grid; grid-template-columns: 1fr 40%; }
        .front::after { position: absolute; width: 210px; height: 210px; right: -80px; bottom: -90px; border: 38px solid #e8dfc53d; border-radius: 50%; content: ''; pointer-events: none; }
        .identity { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: flex-start; padding: 28px 28px 24px 16px; min-width: 0; }
        .brand { display: flex; align-items: baseline; gap: 8px; color: #204533; }
        .brand strong { font: 900 clamp(30px, 5vw, 45px) 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; line-height: 1; }
        .brand span { color: #a88135; font-size: 10px; font-weight: 700; letter-spacing: .13em; }
        .kicker { margin-top: 12px; color: #a17f3b; font-size: 11px; font-weight: 700; }
        .person { display: flex; align-items: center; gap: 12px; min-width: 0; margin-top: auto; }
        .portrait { display: grid; place-items: center; overflow: hidden; width: clamp(68px, 13vw, 102px); aspect-ratio: 1; flex: none; border: 3px solid #c5a95f; border-radius: 13px; background: #dce9d4; color: #234d37; font: 900 38px 'Thmanyah Serif Display', sans-serif; }
        .portrait img { width: 100%; height: 100%; object-fit: cover; }
        .person-name { min-width: 0; font: 900 clamp(17px, 3vw, 24px) 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; line-height: 1.25; overflow-wrap: anywhere; }
        .person-label { margin-top: 6px; color: #657468; font-size: 11px; }
        .code { margin-top: 13px; padding-top: 8px; border-top: 1px solid #d9ceb0; font: 700 13px ui-monospace, monospace; letter-spacing: .05em; }
        .scan { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; background: #19412f; color: #fbf8ee; padding: 26px 15px; text-align: center; }
        .scan-title { font: 900 16px 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; }
        .qr { width: min(78%, 195px); aspect-ratio: 1; padding: 8px; background: white; border-radius: 7px; }
        .qr svg { display: block; width: 100%; height: 100%; }
        .scan p { margin: 0; color: #e8d8a9; font-size: 10px; }
        .back { display: flex; flex-direction: column; justify-content: space-between; padding: 30px 36px; background: #19412f; color: #fbf8ee; }
        .back::after { position: absolute; width: 260px; height: 260px; left: -85px; bottom: -130px; border: 1px solid #bb9a5266; border-radius: 50%; box-shadow: 0 0 0 30px #bb9a5211, 0 0 0 60px #bb9a5211; content: ''; pointer-events: none; }
        .back .mark { color: #d1ad62; font: 900 27px 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; }
        .back h2 { margin: 0 0 12px; color: #e3c47f; font: 900 22px 'Thmanyah Serif Display', 'Thmanyah Sans', sans-serif; }
        .back p { max-width: 420px; margin: 0 0 8px; font-size: 14px; line-height: 1.75; }
        .back footer { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: end; gap: 12px; color: #d8caa5; font-size: 11px; }
        .back footer strong { color: #fff; font: 700 14px ui-monospace, monospace; }
        @page { size: A4 portrait; margin: 14mm; }
        @media print {
            body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet { max-width: none; padding: 0; }
            .toolbar { display: none; }
            .pair { gap: 14mm; }
            .face-wrap { width: 85.6mm; break-inside: avoid; }
            .face-label { font-size: 8pt; }
            .card { width: 85.6mm; height: 54mm; border-radius: 3mm; box-shadow: none; }
            .identity { padding: 4mm 3mm 3mm 4mm; }
            .brand strong { font-size: 20pt; }
            .kicker { margin-top: 2mm; font-size: 7pt; }
            .portrait { width: 15mm; border-radius: 2mm; font-size: 20pt; }
            .person { gap: 2mm; }
            .person-name { font-size: 10pt; }
            .person-label, .scan p { font-size: 6pt; }
            .code { margin-top: 2mm; padding-top: 1mm; font-size: 7pt; }
            .scan { padding: 3mm; gap: 1.5mm; }
            .scan-title { font-size: 9pt; }
            .qr { width: 28mm; padding: 1.5mm; }
            .back { padding: 5mm; }
            .back .mark { font-size: 15pt; }
            .back h2 { margin-bottom: 2mm; font-size: 12pt; }
            .back p { margin-bottom: 1mm; font-size: 8pt; }
            .back footer { font-size: 6pt; }
            .back footer strong { font-size: 8pt; }
        }
        @media (max-width: 480px) {
            .sheet { padding-inline: 12px; }
            .toolbar { align-items: flex-start; flex-direction: column; }
            .identity { padding: 18px 14px; }
            .person { gap: 7px; }
            .person-name { font-size: 15px; }
            .portrait { width: 54px; font-size: 25px; }
            .brand strong { font-size: 25px; }
            .scan { padding: 12px 6px; }
            .qr { width: 78%; padding: 4px; }
            .scan-title { font-size: 11px; }
            .scan p, .person-label, .kicker { font-size: 8px; }
            .back { padding: 16px; }
            .back h2 { font-size: 17px; margin-bottom: 6px; }
            .back p { font-size: 10px; }
        }
    </style>
</head>
<body>
    <main class="sheet">
        <div class="toolbar"><div><h1>بطاقة القائد</h1><p>وجها البطاقة بالمقاس الفعلي 85.6 × 54 مم</p></div><button type="button" class="print" onclick="window.print()">طباعة وجهي البطاقة</button></div>
        <div class="pair">
            <section class="face-wrap" aria-label="الوجه الأمامي"><span class="face-label">الوجه الأمامي</span><article class="card front">
                <div class="identity"><div class="brand"><strong>بيرحاء</strong><span>BYRUHAA</span></div><span class="kicker">بطاقة شراء القائد</span>
                    <div class="person"><div class="portrait">@if ($avatarUrl)<img src="{{ $avatarUrl }}" alt="صورة {{ $minorName }}">@else {{ mb_substr($minorName, 0, 1) }} @endif</div><div><div class="person-name">{{ $minorName }}</div><div class="person-label">قدّم البطاقة عند الشراء</div></div></div>
                    <div class="code" dir="ltr">{{ $memberCode }}</div>
                </div>
                <div class="scan"><strong class="scan-title">امسح للتحقق</strong><div class="qr" aria-label="رمز QR لبطاقة الشراء">{!! $qrSvg !!}</div><p>للاستخدام عند نقطة البيع</p></div>
            </article></section>
            <section class="face-wrap" aria-label="الوجه الخلفي"><span class="face-label">الوجه الخلفي</span><article class="card back"><div class="mark">بيرحاء</div><div><h2>بطاقتي معي</h2><p>استخدم هذه البطاقة للشراء من بيرحاء، واحتفظ بها في مكان آمن.</p><p>عند فقدانها، اطلب من ولي الأمر إيقافها وإصدار بطاقة جديدة.</p></div><footer><span>BYRUHAA · بطاقة القائد</span><strong dir="ltr">{{ $memberCode }}</strong></footer></article></section>
        </div>
    </main>
</body>
</html>
