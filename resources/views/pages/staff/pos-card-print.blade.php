<div>
    <!-- Act only according to that maxim whereby you can, at the same time, will that it should become a universal law. - Immanuel Kant -->
</div>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بطاقة شراء بيرحاء — {{ $memberCode }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #edf3ea; color: #173a2a; font-family: Tahoma, Arial, sans-serif; }
        .sheet { width: min(100%, 540px); padding: 24px; }
        .card { overflow: hidden; border: 2px solid #c8d9bc; border-radius: 26px; background: #fffdf6; box-shadow: 0 18px 50px #173a2a1c; text-align: center; }
        .brand { padding: 26px 24px 20px; background: #173a2a; color: #fff; }
        .brand strong { display: block; font-size: 30px; letter-spacing: .02em; }
        .brand span { display: block; margin-top: 5px; font-size: 13px; color: #e5c87c; }
        .body { padding: 24px 28px 28px; }
        .body h1 { margin: 0 0 5px; font-size: 21px; }
        .body p { margin: 0 0 16px; color: #607464; font-size: 13px; }
        .qr { width: fit-content; margin: 0 auto 16px; padding: 10px; background: #fff; border: 1px solid #e3e9df; border-radius: 14px; }
        .qr svg { display: block; width: min(100%, 260px); height: auto; }
        .code { display: inline-block; padding: 8px 18px; border-radius: 999px; background: #edf3ea; font-size: 17px; font-weight: bold; letter-spacing: .08em; }
        .note { margin: 20px 0 0 !important; line-height: 1.8; }
        .print { display: block; width: 100%; margin-top: 20px; padding: 15px; border: 0; border-radius: 13px; background: #173a2a; color: #fff; font: bold 16px Tahoma, Arial, sans-serif; cursor: pointer; }
        @page { size: A5 portrait; margin: 12mm; }
        @media print { body { min-height: auto; background: #fff; } .sheet { width: 100%; padding: 0; } .card { box-shadow: none; break-inside: avoid; } .print { display: none; } }
    </style>
</head>
<body>
    <main class="sheet">
        <article class="card">
            <header class="brand"><strong>بيرحاء</strong><span>BYRUHAA · بطاقة الشراء</span></header>
            <div class="body">
                <h1>بطاقة القائد</h1>
                <p>قدّم هذه البطاقة للكاشير عند الشراء</p>
                <div class="qr" aria-label="رمز QR لبطاقة الشراء">{!! $qrSvg !!}</div>
                <div class="code" dir="ltr">{{ $memberCode }}</div>
                <p class="note">احتفظ بالبطاقة في مكان آمن. عند فقدانها، يستطيع ولي الأمر إبطالها من حسابه.</p>
            </div>
        </article>
        <button class="print" type="button" onclick="window.print()">طباعة البطاقة</button>
    </main>
</body>
</html>
