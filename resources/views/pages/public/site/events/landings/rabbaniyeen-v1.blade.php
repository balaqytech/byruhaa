@extends('layouts.event-landing')

@php
    $bodyClass = 'rabbaniyeen-landing';
    $whatsappUrl = 'https://wa.me/'.config('coffee.whatsapp_number').'?text='.rawurlencode('السلام عليكم، أريد الاستفسار عن برنامج ربانيين.');
@endphp

@push('head')
<style>
  .rabbaniyeen-landing {
    --forest: #123329;
    --forest-deep: #0b241d;
    --green: #007a52;
    --green-hover: #006746;
    --mint: #f6fbf8;
    --paper: #fffdf7;
    --gold: #b7892b;
    --hibr-2: #4b665c;
    --waraq-2: #ffffff;
    --hd: #dce9e1;
    background: var(--mint);
    color: var(--forest);
    font-family: var(--font-sans);
    font-size: 17px;
    line-height: 1.85;
    -webkit-font-smoothing: antialiased;
  }

  .rabbaniyeen-landing * { box-sizing: border-box; }
  .rabbaniyeen-landing :is(h1, h2, h3, h4) {
    font-family: var(--font-heading);
    font-weight: 900;
    line-height: 1.25;
  }
  .rabbaniyeen-landing p { margin: 0 0 1em; }
  .rabbaniyeen-landing a { color: var(--green); }
  .rabbaniyeen-landing .wrap { width: min(100% - 48px, 1220px); margin-inline: auto; }
  .rabbaniyeen-landing :focus-visible { outline: 3px solid var(--gold); outline-offset: 3px; }
  .rabbaniyeen-landing :is(section, #sajjil) { scroll-margin-top: 90px; }

  .rabbaniyeen-landing header {
    position: sticky;
    inset-block-start: 0;
    z-index: 40;
    background: rgb(246 251 248 / 96%);
    border-block-end: 1px solid var(--hd);
    backdrop-filter: blur(12px);
  }
  .rabbaniyeen-landing .bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    min-height: 82px;
    padding-block: 10px;
  }
  .rabbaniyeen-landing .brand {
    color: var(--forest);
    font-family: var(--font-heading);
    font-size: 26px;
    font-weight: 900;
    line-height: 1.2;
    white-space: nowrap;
  }
  .rabbaniyeen-landing .brand span {
    display: block;
    margin-block-start: 3px;
    color: var(--hibr-2);
    font-family: var(--font-sans);
    font-size: 12px;
    font-weight: 400;
  }
  .rabbaniyeen-landing .nav { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 28px; }
  .rabbaniyeen-landing .nav a {
    color: var(--forest);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: color .18s ease;
  }
  .rabbaniyeen-landing .nav a:hover { color: var(--green); }
  .rabbaniyeen-landing .nav a:last-child {
    padding: 9px 16px;
    border: 1px solid rgb(0 122 82 / 25%);
    border-radius: 3px;
    color: var(--green);
  }
  .rabbaniyeen-landing .nav a:last-child:hover { background: rgb(0 122 82 / 7%); }

  .rabbaniyeen-landing .hero {
    position: relative;
    overflow: hidden;
    padding-block: clamp(64px, 7vw, 112px) clamp(76px, 8vw, 124px);
    background: radial-gradient(circle at 15% 10%, rgb(213 239 223 / 70%), transparent 38%), var(--mint);
  }
  .rabbaniyeen-landing .hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(0, .92fr);
    align-items: center;
    gap: clamp(44px, 6vw, 96px);
  }
  .rabbaniyeen-landing .hero h1 {
    max-width: 12ch;
    margin: 0 0 24px;
    color: var(--forest);
    font-size: clamp(42px, 4.8vw, 68px);
  }
  .rabbaniyeen-landing .hero .lede {
    max-width: 54ch;
    margin-block-end: 18px;
    color: #36594c;
    font-size: clamp(17px, 1.6vw, 21px);
    line-height: 1.9;
  }
  .rabbaniyeen-landing .aya {
    max-width: 58ch;
    margin-block-end: 0;
    color: #87631d;
    font-size: 15px;
    font-weight: 700;
  }
  .rabbaniyeen-landing .cta-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-block-start: 32px;
  }
  .rabbaniyeen-landing .btn {
    display: inline-flex;
    min-height: 52px;
    align-items: center;
    justify-content: center;
    padding: 12px 25px;
    border: 1px solid var(--green);
    border-radius: 3px;
    background: var(--green);
    color: #fff;
    font-family: var(--font-sans);
    font-size: 15px;
    font-weight: 700;
    line-height: 1.5;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s ease, border-color .18s ease, transform .18s ease;
  }
  .rabbaniyeen-landing .btn:hover { transform: translateY(-2px); background: var(--green-hover); border-color: var(--green-hover); }
  .rabbaniyeen-landing .btn-ghost {
    border-color: rgb(18 51 41 / 22%);
    background: rgb(255 255 255 / 68%);
    color: var(--forest);
  }
  .rabbaniyeen-landing .btn-ghost:hover { background: #fff; border-color: var(--green); }
  .rabbaniyeen-landing .no-commit {
    margin-block: 15px 0;
    color: var(--hibr-2);
    font-size: 13px;
  }
  .rabbaniyeen-landing .mushaf {
    position: relative;
    margin: 0;
    padding: clamp(22px, 3vw, 40px);
    border: 6px solid #eaf4ed;
    border-radius: 4px;
    background: var(--paper);
    box-shadow: 20px 22px 0 rgb(18 51 41 / 9%), 0 22px 60px rgb(18 51 41 / 9%);
  }
  .rabbaniyeen-landing .mushaf svg { display: block; width: 100%; height: auto; }
  .rabbaniyeen-landing .mushaf figcaption {
    margin-block-start: 22px;
    padding-block-start: 16px;
    border-block-start: 1px solid #e7e6d8;
    color: var(--forest);
    font-size: 14px;
    font-weight: 700;
    text-align: center;
  }
  .rabbaniyeen-landing .mushaf b { color: #87631d; }

  .rabbaniyeen-landing section { padding-block: clamp(72px, 8vw, 120px); }
  .rabbaniyeen-landing .sec-t {
    max-width: 22ch;
    margin: 0 0 16px;
    color: var(--forest);
    font-size: clamp(30px, 3.3vw, 45px);
  }
  .rabbaniyeen-landing .sec-s {
    max-width: 65ch;
    margin: 0 0 44px;
    color: var(--hibr-2);
    font-size: 17px;
  }

  .rabbaniyeen-landing .time { border-block: 1px solid var(--hd); background: #fff; }
  .rabbaniyeen-landing .time-grid {
    display: grid;
    grid-template-columns: minmax(0, .72fr) minmax(0, 1.28fr);
    align-items: start;
    gap: clamp(40px, 8vw, 120px);
    margin-block-start: 52px;
  }
  .rabbaniyeen-landing .bignum {
    color: var(--green);
    font-family: var(--font-heading);
    font-size: clamp(90px, 12vw, 168px);
    font-weight: 900;
    line-height: .95;
  }
  .rabbaniyeen-landing .bignum small {
    display: block;
    max-width: 18ch;
    margin-block-start: 18px;
    color: var(--forest);
    font-family: var(--font-sans);
    font-size: 19px;
    font-weight: 700;
    line-height: 1.6;
  }
  .rabbaniyeen-landing .ledger {
    max-width: none;
    margin: 0 0 30px;
    padding: 0;
    color: var(--hibr-2);
    font-size: 16px;
    list-style: none;
  }
  .rabbaniyeen-landing .ledger li {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding-block: 13px;
    border-block-end: 1px solid var(--hd);
  }
  .rabbaniyeen-landing .ledger li span:last-child { white-space: nowrap; }
  .rabbaniyeen-landing .ledger li:last-child { color: var(--forest); font-weight: 700; }
  .rabbaniyeen-landing .time-grid p { color: var(--hibr-2); }

  .rabbaniyeen-landing #masarat { background: var(--mint); }
  .rabbaniyeen-landing .tracks {
    display: grid;
    grid-template-columns: 1.3fr 1fr 1fr;
    gap: 15px;
  }
  .rabbaniyeen-landing .track {
    padding: clamp(24px, 2.6vw, 38px);
    border: 1px solid var(--hd);
    border-radius: 3px;
    background: #fff;
  }
  .rabbaniyeen-landing .track:first-child { border-color: #a6d5bb; background: #e9f5ed; }
  .rabbaniyeen-landing .track h3 { margin: 0 0 6px; color: var(--forest); font-size: 28px; }
  .rabbaniyeen-landing .track .wird {
    min-height: 34px;
    margin: 0 0 22px;
    color: var(--green);
    font-size: 17px;
    font-weight: 700;
  }
  .rabbaniyeen-landing .track dl { display: grid; grid-template-columns: 1fr auto; margin: 0; font-size: 15px; }
  .rabbaniyeen-landing .track dt, .rabbaniyeen-landing .track dd { margin: 0; padding-block: 10px; border-block-start: 1px solid rgb(18 51 41 / 12%); }
  .rabbaniyeen-landing .track dt { color: var(--hibr-2); }
  .rabbaniyeen-landing .track dd { color: var(--forest); font-weight: 700; text-align: end; }
  .rabbaniyeen-landing .track:nth-child(3) { border-block-start: 3px solid var(--gold); }
  .rabbaniyeen-landing #masarat .tracks + p { font-size: 16px; }

  .rabbaniyeen-landing .feat { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 50px 72px; }
  .rabbaniyeen-landing .f { padding-block-start: 25px; border-block-start: 2px solid #acd5bb; }
  .rabbaniyeen-landing .f h3 { margin: 0 0 14px; color: var(--forest); font-size: 26px; }
  .rabbaniyeen-landing .f p { margin: 0; color: var(--hibr-2); font-size: 16px; line-height: 1.95; }

  .rabbaniyeen-landing #asila { background: var(--mint); }
  .rabbaniyeen-landing details { margin-block-end: 10px; border: 1px solid var(--hd); border-radius: 3px; background: #fff; }
  .rabbaniyeen-landing details[open] { border-color: #acd5bb; box-shadow: 0 10px 30px rgb(18 51 41 / 4%); }
  .rabbaniyeen-landing summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 19px 22px;
    color: var(--forest);
    font-size: 17px;
    font-weight: 700;
    list-style: none;
    cursor: pointer;
  }
  .rabbaniyeen-landing summary::-webkit-details-marker { display: none; }
  .rabbaniyeen-landing summary::after { content: "+"; flex: none; color: var(--green); font-size: 25px; line-height: 1; }
  .rabbaniyeen-landing details[open] summary::after { content: "–"; }
  .rabbaniyeen-landing details .body { padding: 0 22px 23px; color: var(--hibr-2); font-size: 16px; line-height: 1.95; }

  .rabbaniyeen-landing .form-sec { background: var(--forest); color: #fff; }
  .rabbaniyeen-landing .form-sec > .wrap {
    display: grid;
    grid-template-columns: minmax(0, .72fr) minmax(0, 1.28fr);
    align-items: start;
    column-gap: clamp(48px, 7vw, 112px);
  }
  .rabbaniyeen-landing .form-sec .sec-t { grid-column: 1; grid-row: 1; margin-block-start: 32px; color: #fff; }
  .rabbaniyeen-landing .form-sec .sec-s { grid-column: 1; grid-row: 2; color: #c4dbcd; }
  .rabbaniyeen-landing .card {
    grid-column: 2;
    grid-row: 1 / span 3;
    width: 100%;
    max-width: none;
    padding: clamp(24px, 3.2vw, 46px);
    border-radius: 4px;
    background: #fff;
    color: var(--forest);
    box-shadow: 0 24px 70px rgb(0 0 0 / 13%);
  }
  .rabbaniyeen-landing .pledge {
    margin-block-end: 28px;
    padding: 15px 18px;
    border-inline-start: 3px solid var(--green);
    background: var(--mint);
    color: var(--forest);
    font-size: 14px;
    line-height: 1.8;
  }
  .rabbaniyeen-landing .field { margin-block-end: 20px; }
  .rabbaniyeen-landing label { display: block; margin-block-end: 7px; color: var(--forest); font-size: 15px; font-weight: 700; }
  .rabbaniyeen-landing .req { color: #b0392e; }
  .rabbaniyeen-landing :is(input, select, textarea) {
    width: 100%;
    min-height: 48px;
    padding: 10px 13px;
    border: 1px solid #cbdcd1;
    border-radius: 3px;
    background: #fff;
    color: var(--forest);
    font-family: var(--font-sans);
    font-size: 15px;
  }
  .rabbaniyeen-landing :is(input, select, textarea):focus {
    border-color: var(--green);
    outline: none;
    box-shadow: 0 0 0 3px rgb(0 122 82 / 14%);
  }
  .rabbaniyeen-landing textarea { min-height: 106px; resize: vertical; }
  .rabbaniyeen-landing .hint { margin-block-start: 5px; color: var(--hibr-2); font-size: 13px; }
  .rabbaniyeen-landing .two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  .rabbaniyeen-landing .check { display: flex; align-items: flex-start; gap: 11px; color: var(--hibr-2); font-size: 14px; font-weight: 400; line-height: 1.8; }
  .rabbaniyeen-landing .check input { flex: none; width: 19px; min-height: 19px; height: 19px; margin-block-start: 5px; accent-color: var(--green); }
  .rabbaniyeen-landing .submit { width: 100%; margin-block-start: 7px; font-size: 17px; }
  .rabbaniyeen-landing .submit[disabled] { opacity: .55; cursor: not-allowed; }
  .rabbaniyeen-landing .err { display: none; margin-block-start: 8px; color: #b0392e; font-size: 14px; }
  .rabbaniyeen-landing .done { display: none; padding-block: 16px; text-align: center; }
  .rabbaniyeen-landing .done h3 { margin: 0 0 10px; color: var(--green); font-size: 28px; }
  .rabbaniyeen-landing .done p { color: var(--hibr-2); }
  .rabbaniyeen-landing .wa {
    display: inline-flex;
    min-height: 48px;
    align-items: center;
    margin-block-start: 12px;
    padding: 10px 25px;
    border-radius: 3px;
    background: #1f9e52;
    color: #fff;
    font-weight: 700;
    text-decoration: none;
  }

  .rabbaniyeen-landing footer { padding-block: 64px 30px; background: var(--forest-deep); color: #b3c8bb; font-size: 14px; }
  .rabbaniyeen-landing footer a { display: block; padding-block: 3px; color: #cbdcd1; text-decoration: none; }
  .rabbaniyeen-landing footer a:hover { color: #fff; text-decoration: underline; }
  .rabbaniyeen-landing .fgrid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 48px; }
  .rabbaniyeen-landing .fgrid h4 { margin: 0 0 16px; color: #fff; font-size: 20px; }
  .rabbaniyeen-landing .fine { margin-block: 54px 0; padding-block-start: 22px; border-block-start: 1px solid rgb(255 255 255 / 15%); color: #9ab2a3; font-size: 13px; }

  @media (max-width: 980px) {
    .rabbaniyeen-landing .nav { gap: 10px 16px; }
    .rabbaniyeen-landing .hero-grid { gap: 40px; }
    .rabbaniyeen-landing .tracks { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rabbaniyeen-landing .track:first-child { grid-column: 1 / -1; }
    .rabbaniyeen-landing .track { padding: 22px; }
  }
  @media (max-width: 760px) {
    .rabbaniyeen-landing .wrap { width: min(100% - 36px, 1220px); }
    .rabbaniyeen-landing .bar { min-height: 68px; }
    .rabbaniyeen-landing .brand { font-size: 22px; }
    .rabbaniyeen-landing .nav { display: none; }
    .rabbaniyeen-landing .hero-grid, .rabbaniyeen-landing .time-grid { grid-template-columns: 1fr; }
    .rabbaniyeen-landing .hero h1 { max-width: 16ch; font-size: clamp(37px, 8vw, 52px); }
    .rabbaniyeen-landing .mushaf { max-width: 570px; }
    .rabbaniyeen-landing .time-grid { margin-block-start: 30px; gap: 36px; }
    .rabbaniyeen-landing .bignum { font-size: clamp(90px, 20vw, 130px); }
    .rabbaniyeen-landing .tracks { grid-template-columns: 1fr; }
    .rabbaniyeen-landing .track:first-child { grid-column: auto; }
    .rabbaniyeen-landing .track .wird { min-height: 0; }
    .rabbaniyeen-landing .feat { grid-template-columns: 1fr; gap: 34px; }
    .rabbaniyeen-landing .form-sec > .wrap { display: block; }
    .rabbaniyeen-landing .form-sec .sec-t { margin-block-start: 0; }
    .rabbaniyeen-landing .form-sec .sec-s { margin-block-end: 30px; }
    .rabbaniyeen-landing .fgrid { grid-template-columns: 1fr 1fr; gap: 30px; }
    .rabbaniyeen-landing .fgrid > div:first-child { grid-column: 1 / -1; }
  }
  @media (max-width: 520px) {
    .rabbaniyeen-landing .wrap { width: min(100% - 32px, 1220px); }
    .rabbaniyeen-landing .hero { padding-block: 58px 76px; }
    .rabbaniyeen-landing .hero h1 { font-size: 39px; }
    .rabbaniyeen-landing .cta-row { align-items: stretch; }
    .rabbaniyeen-landing .cta-row .btn { flex: 1 1 100%; }
    .rabbaniyeen-landing .mushaf { padding: 18px; box-shadow: 10px 12px 0 rgb(18 51 41 / 9%); }
    .rabbaniyeen-landing .ledger { font-size: 14px; }
    .rabbaniyeen-landing .two { grid-template-columns: 1fr; gap: 0; }
    .rabbaniyeen-landing .card { padding: 22px 18px; }
    .rabbaniyeen-landing .fgrid { grid-template-columns: 1fr; }
    .rabbaniyeen-landing .fgrid > div:first-child { grid-column: auto; }
  }
  @media (prefers-reduced-motion: reduce) {
    .rabbaniyeen-landing :is(.btn, .nav a) { transition: none; }
    .rabbaniyeen-landing .btn:hover { transform: none; }
  }
</style>
@endpush

@section('content')
<!-- ═════════ الترويسة ═════════ -->
<header>
  <div class="wrap bar">
    <div class="brand">ربانيين<span>من بِيرُحاء إبراء · مجموعة العيسري</span></div>
    <nav class="nav">
      <a href="#kayf">كيف يمشي اليوم</a>
      <a href="#masarat">المسارات</a>
      <a href="#asila">أسئلة الأهل</a>
      <a href="#sajjil">سجِّل اهتمامك</a>
    </nav>
  </div>
</header>

<!-- ═════════ البطل ═════════ -->
<div class="hero">
  <div class="wrap hero-grid">
    <div>
      <h1>سطران في اليوم، ومشرفٌ يعرف ابنك باسمه.</h1>
      <p class="lede">برنامجُ حفظٍ منظَّمٌ للفتيان من الصف السابع إلى الثاني عشر: وِردٌ يوميٌّ عن بُعد، وليلتان في بِيرُحاء كلَّ شهر. لا نطلب منك متابعةً، ولا نحجب عنك خبرًا.</p>
      <p class="aya">﴿كونوا رَبّانِيّينَ بِما كُنتُم تُعَلِّمونَ الكِتابَ وَبِما كُنتُم تَدرُسونَ﴾</p>
      <div class="cta-row">
        <a class="btn" href="#sajjil">سجِّل اهتمامك</a>
        <a class="btn btn-ghost" href="#kayf">كيف يمشي يومُ الفتى؟</a>
      </div>
      <p class="no-commit">التسجيلُ اليومَ إبداءُ اهتمامٍ فقط، بلا رسمٍ، وبلا توقيع، وبلا التزام.</p>
    </div>

    <figure class="mushaf">
      <!-- لوحُ الوِرد: صفحةٌ من خمسة عشر سطرًا، سطران منها هما وِردُ اليوم -->
      <svg viewBox="0 0 300 200" role="img" aria-label="رسمٌ لصفحةٍ من المصحف من خمسة عشر سطرًا، سطران منها مُميَّزان بلون ذهبي بوصفهما وِرْدَ اليوم">
        <rect x="12" y="8" width="276" height="184" rx="6" fill="#FFFDF6" stroke="#DCD5BE" stroke-width="1.4"/>
        <rect x="22" y="18" width="256" height="164" rx="3" fill="none" stroke="#E6DFC8" stroke-width="1"/>
        <g stroke="#C8CFD6" stroke-width="3.2" stroke-linecap="round">
          <line x1="34" y1="30" x2="266" y2="30"/>
          <line x1="34" y1="41" x2="252" y2="41"/>
          <line x1="34" y1="52" x2="266" y2="52"/>
          <line x1="34" y1="63" x2="240" y2="63"/>
          <line x1="34" y1="74" x2="266" y2="74"/>
          <line x1="34" y1="85" x2="258" y2="85"/>
        </g>
        <!-- وِردُ اليوم -->
        <g stroke="#B7892B" stroke-width="4" stroke-linecap="round">
          <line x1="34" y1="99" x2="266" y2="99"/>
          <line x1="34" y1="111" x2="228" y2="111"/>
        </g>
        <g stroke="#C8CFD6" stroke-width="3.2" stroke-linecap="round">
          <line x1="34" y1="125" x2="266" y2="125"/>
          <line x1="34" y1="136" x2="248" y2="136"/>
          <line x1="34" y1="147" x2="266" y2="147"/>
          <line x1="34" y1="158" x2="236" y2="158"/>
          <line x1="34" y1="169" x2="266" y2="169"/>
        </g>
      </svg>
      <figcaption>هذا هو وِردُ اليوم في مسار السكينة: <b>ثُمنُ صفحة</b>.</figcaption>
    </figure>
  </div>
</div>

<!-- ═════════ الوقت ═════════ -->
<section class="time" id="kayf">
  <div class="wrap">
    <h2 class="sec-t">المشكلةُ ليست أنّ ابنك لا يملك وقتًا.</h2>
    <p class="sec-s">بل أنّ وقتَه غيرُ مجدوَل. احسبها معنا بلا تجميل:</p>
    <div class="time-grid">
      <div>
        <div class="bignum">٨١٪<small>من عام ابنك خارج المدرسة وواجباتها</small></div>
      </div>
      <div>
        <ul class="ledger">
          <li><span>ساعاتُ السنة كاملةً</span><span>٨٧٦٠ ساعة</span></li>
          <li><span>أيامُ الدوام المدرسي</span><span>١٦٥ يومًا</span></li>
          <li><span>المدرسةُ والواجباتُ في اليوم</span><span>١٠ ساعات</span></li>
          <li><span>ما تأخذه المدرسةُ من العام</span><span>١٦٥٠ ساعة</span></li>
          <li><span>ما يبقى بيده</span><span>٧١١٠ ساعة</span></li>
        </ul>
        <p style="margin-top:20px;max-width:52ch">ولذلك لا نقول لابنك «كرِّر عشر مرات». نقول له شيئًا واحدًا: اختر لحظةً ثابتةً في يومك، بعد صلاةٍ بعينها، أو قبل النوم مباشرةً، واربط بها سطرين. الساعةُ تُنسى، والصلاةُ لا تُنسى.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════ المسارات ═════════ -->
<section id="masarat">
  <div class="wrap">
    <h2 class="sec-t">ثلاثةُ مسارات، يُدخَل إليها بالقدرة لا بالسنّ</h2>
    <p class="sec-s">يبدأ كلُّ فتًى من مسار السكينة أربعةَ أسابيع مهما كانت قدرتُه، ثم يرتفع حين يطلب هو. والأرقامُ أدناه حدٌّ أدنى نلتزم به، لا سقفٌ نَعِد به.</p>
    <div class="tracks">
      <div class="track">
        <h3>السكينة</h3>
        <p class="wird">ثُمنُ صفحة · سطران</p>
        <dl>
          <dt>أيام الأسبوع</dt><dd>٤ أيام</dd>
          <dt>حصيلةُ العام</dt><dd>جزءٌ واحد</dd>
          <dt>حصيلةُ ست سنوات</dt><dd>ستة أجزاء</dd>
        </dl>
      </div>
      <div class="track">
        <h3>النور</h3>
        <p class="wird">ربعُ صفحة</p>
        <dl>
          <dt>أيام الأسبوع</dt><dd>٤ أيام</dd>
          <dt>حصيلةُ العام</dt><dd>جزءان</dd>
          <dt>حصيلةُ ست سنوات</dt><dd>اثنا عشر جزءًا</dd>
        </dl>
      </div>
      <div class="track">
        <h3>الهجرة</h3>
        <p class="wird">نصفُ صفحة</p>
        <dl>
          <dt>أيام الأسبوع</dt><dd>٤ أيام</dd>
          <dt>حصيلةُ العام</dt><dd>أربعة أجزاء</dd>
          <dt>حصيلةُ ست سنوات</dt><dd>أربعة وعشرون جزءًا</dd>
        </dl>
      </div>
    </div>
    <p style="margin-top:26px;color:var(--hibr-2);max-width:66ch">وقبل الحفظ بوّابةٌ واحدة: اختبارُ تصنيفٍ في التلاوة. فمن أتقن دخل الحفظَ مباشرةً، ومن دونه دخل «المستوى الأول لإتقان التلاوة» بضعةَ أسابيعَ ثم لحق. ولا يُردُّ أحدٌ على الباب.</p>
  </div>
</section>

<!-- ═════════ المميزات ═════════ -->
<section style="background:var(--waraq-2);border-block:1px solid var(--hd)">
  <div class="wrap">
    <h2 class="sec-t">أربعةُ أشياءَ نفعلها ولا تُفعَل في غيرنا</h2>
    <p class="sec-s">اقرأها ثم اسأل بها أيَّ برنامجٍ آخر.</p>
    <div class="feat">
      <div class="f">
        <h3>مَن يُعلِّم لا يُجيز</h3>
        <p>إجازةُ الجزء لا يمنحها معلِّمُ ابنك، بل مُسمِّعٌ مستقلٌّ لا يعرفه ولا يُقاس بأدائه. فلا نسأل رجلًا أن يشهد على عمله، وتصير كلمةُ «أتقن» تعني الشيءَ نفسَه عند الجميع.</p>
      </div>
      <div class="f">
        <h3>ليلتان في بيرحاء كلَّ شهر</h3>
        <p>من الخميس إلى السبت: تسميعٌ وإجازاتٌ ولقاءُ المشرف وجهًا لوجه، وبينها ملعبٌ وقهوةٌ وسينما وميدانُ رماية. وِردُه اليوميُّ تذكرتُه إليها.</p>
      </div>
      <div class="f">
        <h3>وضعُ الصيانة في الامتحانات</h3>
        <p>قبل اختباراته بأسبوعين يتوقّف الحفظُ الجديدُ تلقائيًّا، ويبقى خمسُ دقائقَ مراجعةً في اليوم. لا انقطاع، ولا اشتراكٌ معلَّق، ولا إحساسٌ بالذنب، ويصلك إشعارٌ بذلك.</p>
      </div>
      <div class="f">
        <h3>لا نُحمِّلك متابعة</h3>
        <p>لن نطلب منك خمسَ دقائقَ في الأسبوع. المسؤوليةُ على ابنك وحدَه. أمّا أنت فترى تقدُّمَه وحضورَه، ويصلك كلَّ شهرٍ تسجيلُ صوته وهو يقرأ، ترجع إليه بعد سنةٍ فتسمع الفرق.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════ الأسئلة ═════════ -->
<section id="asila">
  <div class="wrap" style="max-width:820px">
    <h2 class="sec-t">أسئلةٌ يسألها كلُّ أب</h2>
    <p class="sec-s">أجوبةٌ صريحة. وإن لم تجد سؤالك فاسأله في الاستمارة أسفل الصفحة.</p>

    <details open>
      <summary>ماذا يعني «سجِّل اهتمامك»؟ وهل أدفع شيئًا؟</summary>
      <div class="body">لا. التسجيلُ في هذه المرحلة إبداءُ اهتمامٍ فقط: لا رسمَ، ولا توقيعَ عقد، ولا التزامَ ماليًّا ولا قانونيًّا. أنت تأذن لنا بشيءٍ واحد: أن نُبلِغك حين يُفتَح التسجيلُ الفعليّ. وعندها ترى الرسمَ والعقدَ كاملَين ثم تقرِّر.</div>
    </details>

    <details>
      <summary>متى ينطلق البرنامج؟</summary>
      <div class="body">نستهدف انطلاقَ الدفعة الأولى في يناير ٢٠٢٧م، مع بداية الفصل الدراسي الثاني. وسنُخبرك بالموعد الدقيق قبل الفتح بوقتٍ يكفي للقرار. ولن نفتح قبل أن يكتمل شرطٌ واحد: أن يكون لكلِّ فتًى مشرفٌ باسمه ومعلِّمٌ اجتاز اعتمادَنا.</div>
    </details>

    <details>
      <summary>«جزءٌ في العام»… أليس هذا قليلًا؟</summary>
      <div class="body">سؤالٌ عادل. نعم، ترى برامجَ تعِد بخمسة أجزاءٍ في صيفٍ واحد. والفرقُ بين حفظٍ يبقى وحفظٍ يذهب هو الفرقُ بين الوعدَين: المقدارُ الكبيرُ في الزمن القصير يتفلّت بعد شهرين، فيخرج ابنك وقد «ختم» ولا يملك سورةً يقوم بها في رمضان. وابنٌ دخل في السابع وخرج من الثاني عشر بجزءين في كلِّ عام يخرج باثني عشر جزءًا راسخة، وهي خيرٌ من ثلاثين متفلِّتة.</div>
    </details>

    <details>
      <summary>ابني لا يُتقن التلاوة. هل يُقبَل؟</summary>
      <div class="body">يُقبَل. ولن نقول له يومًا إنه سيدخل «دورةً لتعلُّم القراءة»، بل يدخل «المستوى الأول لإتقان التلاوة»، وهذا اسمُه الحقيقيُّ لا تلطيفًا له. وأسابيعُ قليلةٌ في التأسيس تُوفِّر عليه سنواتٍ من التعثّر، لأنّ من يحفظ وهو لا يُتقن القراءة يحفظ الخطأَ ويُثبِّته بالتكرار.</div>
    </details>

    <details>
      <summary>ابني خارج إبراء. هل يستطيع الالتحاق؟</summary>
      <div class="body">نعم. الوِردُ اليوميُّ والتسميعُ عن بُعد، والحضورُ ليلتين في الشهر فقط. والاعتمادُ الوحيدُ عليك ماليٌّ ونقلُ ابنك إلى لقاء بيرحاء الشهري، وما عدا ذلك يُدار بين ابنك ومشرفه.</div>
    </details>

    <details>
      <summary>ماذا عن خصوصية ابني وصورته؟</summary>
      <div class="body">لا نُصوِّر وجوهَ القُصَّر ولا نُعلِن أسماءهم الكاملة إلا بموافقةٍ خطيةٍ منك، والألقابُ هي الأصل عندنا. ولا لوحاتِ صدارةٍ ولا ترتيبَ حفّاظٍ ولا مقارنةَ فتًى بفتًى في أيِّ واجهةٍ أو رسالة. وبياناتُ ابنك تُعالَج وفق قانون حماية البيانات الشخصية العُماني (المرسوم ٦/٢٠٢٢).</div>
    </details>
  </div>
</section>

<!-- ═════════ الاستمارة ═════════ -->
<section class="form-sec" id="sajjil">
  <div class="wrap">
    <h2 class="sec-t">سجِّل اهتمامك</h2>
    <p class="sec-s">دقيقةٌ واحدة. ولن يصلك منّا إلا ما يخصُّ هذا البرنامج.</p>

    <div class="card">
      <div class="pledge">
        <strong>ما نلتزم به الآن:</strong> لا رسمَ، ولا توقيعَ عقد، ولا التزامَ ماليًّا ولا قانونيًّا. نُبلِغك حين نفتح، وأنت حرٌّ بعدها.
      </div>

      @if (session('rabbaniyeen_interest_reference'))
        <div class="done" role="status" style="display:block">
          <h3>وصلَنا اسمُك.</h3>
          <p>سنُبلِغك حين يُفتَح التسجيل. وإن أحببتَ أن تسأل الآن، فتواصل مع فريق بيرحاء على الواتساب.</p>
          <a class="wa" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">تحدَّث معنا على الواتساب</a>
          <p class="hint" style="margin-top:16px">رقمُ طلبك: <b>{{ session('rabbaniyeen_interest_reference') }}</b></p>
        </div>
      @else
        <form id="f" method="POST" action="{{ route('events.rabbaniyeen.interests.store', $event) }}#sajjil">
          @csrf
          @if ($errors->any())
            <p class="err" role="alert" style="display:block">تحقَّق من البيانات المطلوبة ثم أعِد الإرسال.</p>
          @endif
          <div class="field">
            <label for="guardian_name">اسمُ وليِّ الأمر <span class="req">*</span></label>
            <input id="guardian_name" name="guardian_name" type="text" autocomplete="name" value="{{ old('guardian_name') }}" required>
            @error('guardian_name') <p class="err" style="display:block">{{ $message }}</p> @enderror
          </div>
          <div class="two">
            <div class="field">
              <label for="whatsapp_number">رقمُ الواتساب <span class="req">*</span></label>
              <input id="whatsapp_number" name="whatsapp_number" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('whatsapp_number') }}" placeholder="968 0000 0000" required>
              @error('whatsapp_number') <p class="err" style="display:block">{{ $message }}</p> @enderror
            </div>
            <div class="field">
              <label for="wilaya">الولاية <span class="req">*</span></label>
              <input id="wilaya" name="wilaya" type="text" value="{{ old('wilaya') }}" placeholder="إبراء" required>
              @error('wilaya') <p class="err" style="display:block">{{ $message }}</p> @enderror
            </div>
          </div>
          <div class="two">
            <div class="field">
              <label for="student_grade">صفُّ الابن <span class="req">*</span></label>
              <select id="student_grade" name="student_grade" required>
                <option value="">اختر…</option>
                @foreach (['7' => 'السابع', '8' => 'الثامن', '9' => 'التاسع', '10' => 'العاشر', '11' => 'الحادي عشر', '12' => 'الثاني عشر', 'multiple' => 'لديّ أكثر من ابنٍ في صفوفٍ مختلفة'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('student_grade') == $value)>{{ $label }}</option>
                @endforeach
              </select>
              @error('student_grade') <p class="err" style="display:block">{{ $message }}</p> @enderror
            </div>
            <div class="field">
              <label for="recitation_level">هل يقرأ المصحفَ المشكولَ بطلاقة؟</label>
              <select id="recitation_level" name="recitation_level">
                <option value="">اختر…</option>
                @foreach (['fluent' => 'نعم، يقرأ بطلاقة', 'hesitant' => 'يقرأ بتعثُّرٍ يسير', 'not_fluent' => 'لا يقرأ جيدًا', 'unsure' => 'لستُ متأكِّدًا'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('recitation_level') === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <p class="hint">لا يُبنى عليها قبولٌ ولا ردّ، اختبارُ التصنيف هو الفيصل.</p>
            </div>
          </div>
          <div class="field">
            <label for="preferred_track">المسارُ الذي تراه مناسبًا مبدئيًّا</label>
            <select id="preferred_track" name="preferred_track">
              <option value="">اختر…</option>
              @foreach (['sakinah' => 'السكينة، ثُمنُ صفحة', 'nur' => 'النور، ربعُ صفحة', 'hijrah' => 'الهجرة، نصفُ صفحة', 'choose_after_test' => 'اختاروا أنتم بعد الاختبار'] as $value => $label)
                <option value="{{ $value }}" @selected(old('preferred_track') === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="note">سؤالٌ أو ملاحظة</label>
            <textarea id="note" name="note" placeholder="اكتب ما تحبُّ أن تعرفه قبل أن تقرِّر…">{{ old('note') }}</textarea>
            @error('note') <p class="err" style="display:block">{{ $message }}</p> @enderror
          </div>
          <div class="field">
            <label class="check">
              <input id="contact_consent" name="contact_consent" value="1" type="checkbox" @checked(old('contact_consent')) required>
              <span>أوافق على أن تتواصل معي بِيرُحاء إبراء عبر الواتساب بخصوص برنامج ربانيين، وقد قرأتُ <a href="{{ route('policies.show', 'privacy') }}" target="_blank" rel="noopener">سياسة الخصوصية</a>. وأعلم أنّ هذا التسجيل لا يُلزِمني بشيء.</span>
            </label>
            @error('contact_consent') <p class="err" style="display:block">{{ $message }}</p> @enderror
          </div>
          <button class="btn submit" type="submit">سجِّل اهتمامي، بلا التزام</button>
        </form>
      @endif
    </div>
  </div>
</section>

<!-- ═════════ التذييل ═════════ -->
<footer>
  <div class="wrap">
    <div class="fgrid">
      <div>
        <h4>ربانيين من بِيرُحاء إبراء</h4>
        <p>برنامجُ حفظ القرآن للفتيان من الصف السابع إلى الثاني عشر. مخيم بِيرُحاء، ولاية إبراء، سلطنة عُمان.</p>
      </div>
      <div>
        <h4>تعرّف علينا</h4>
        <a href="{{ route('home') }}">الرئيسية</a>
        <a href="{{ route('about') }}">من نحن</a>
        <a href="{{ route('events.index') }}">الفعاليات</a>
      </div>
      <div>
        <h4>الخصوصية والتواصل</h4>
        <a href="{{ route('policies.show', 'privacy') }}">سياسة الخصوصية</a>
        <a href="{{ route('contact') }}">تواصل معنا</a>
      </div>
    </div>
    <p class="fine">© ٢٠٢٦ بِيرُحاء إبراء · مجموعة العيسري. جميع الحقوق محفوظة.</p>
  </div>
</footer>
@endsection
