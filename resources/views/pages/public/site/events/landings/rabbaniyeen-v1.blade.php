@extends('layouts.event-landing')

@php
    $bodyClass = 'rabbaniyeen-landing';
    $whatsappUrl = 'https://wa.me/'.config('coffee.whatsapp_number').'?text='.rawurlencode('السلام عليكم، أريد الاستفسار عن برنامج ربانيين.');
@endphp

@push('head')
<style>

/* ═══════════════════════════════════════════════════════════
   هوية بِيرُحاء البصرية — لا تُخالَف
   ═══════════════════════════════════════════════════════════ */
:root{
  --kohli:#16263F;        /* كحلي — الأساس */
  --kohli-2:#22374F;
  --fayrouzi:#0E7C7B;     /* فيروزي — الفعل والروابط */
  --fayrouzi-d:#0A5F5E;
  --thahabi:#B7892B;      /* ذهبي — نادر، للتمييز فقط */
  --waraq:#F5F6F4;        /* أرضية الصفحة */
  --waraq-2:#FFFFFF;
  --hibr:#1D2A38;
  --hibr-2:#516071;
  --hd:#E2E6E4;
  --r:14px;
  --wide:1080px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto} *{animation:none!important;transition:none!important}}
body{
  margin:0;background:var(--waraq);color:var(--hibr);
  font-family:"Noto Naskh Arabic",serif;font-size:17px;line-height:2;
  -webkit-font-smoothing:antialiased;
}
h1,h2,h3,.kufi{font-family:"Reem Kufi","Noto Naskh Arabic",sans-serif;line-height:1.55;font-weight:600}
p{margin:0 0 1em}
a{color:var(--fayrouzi-d)}
.wrap{max-width:var(--wide);margin-inline:auto;padding-inline:22px}
:focus-visible{outline:3px solid var(--thahabi);outline-offset:3px;border-radius:6px}

/* ── الترويسة ─────────────────────────────────────────── */
header{background:var(--kohli);color:#fff;position:sticky;top:0;z-index:40}
.bar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0}
.brand{font-family:"Reem Kufi",sans-serif;font-size:19px;font-weight:600;letter-spacing:.2px}
.brand span{display:block;font-size:12.5px;font-weight:400;opacity:.72;font-family:"Noto Naskh Arabic",serif}
.nav{display:flex;gap:22px;font-size:15px}
.nav a{color:#D6DEE6;text-decoration:none}
.nav a:hover{color:#fff}
@media(max-width:760px){.nav{display:none}}

/* ── البطل ────────────────────────────────────────────── */
.hero{background:var(--kohli);color:#fff;padding:56px 0 70px;position:relative;overflow:hidden}
.hero-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:52px;align-items:center}
@media(max-width:880px){.hero-grid{grid-template-columns:1fr;gap:36px}}
.hero h1{font-size:clamp(30px,5.4vw,46px);margin:0 0 18px;font-weight:700}
.hero .lede{font-size:19px;color:#CBD6DF;max-width:52ch;margin-bottom:8px}
.aya{color:var(--thahabi);font-size:15.5px;margin-bottom:22px}
.cta-row{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-top:26px}
.btn{
  display:inline-block;background:var(--fayrouzi);color:#fff;text-decoration:none;
  padding:15px 32px;border-radius:var(--r);font-family:"Reem Kufi",sans-serif;font-size:17px;
  border:0;cursor:pointer;transition:background .18s
}
.btn:hover{background:var(--fayrouzi-d)}
.btn-ghost{background:transparent;border:1.5px solid rgba(255,255,255,.35);color:#fff}
.btn-ghost:hover{background:rgba(255,255,255,.09)}
.no-commit{font-size:14.5px;color:#9FB0BF;margin-top:14px}

/* لوح المصحف — رمز الوِرد */
.mushaf{background:#F8F7F2;border-radius:18px;padding:26px 22px 20px;box-shadow:0 18px 44px rgba(0,0,0,.28)}
.mushaf svg{width:100%;height:auto;display:block}
.mushaf figcaption{
  font-family:"Reem Kufi",sans-serif;font-size:14.5px;color:var(--kohli);
  text-align:center;margin-top:14px;padding-top:12px;border-top:1px solid #E4E0D2
}
.mushaf b{color:var(--thahabi)}

/* ── الأقسام ──────────────────────────────────────────── */
section{padding:64px 0}
.sec-t{font-size:clamp(23px,3.6vw,30px);color:var(--kohli);margin:0 0 12px}
.sec-s{color:var(--hibr-2);max-width:60ch;margin:0 0 34px}

/* الوقت */
.time{background:var(--waraq-2);border-block:1px solid var(--hd)}
.time-grid{display:grid;grid-template-columns:auto 1fr;gap:40px;align-items:center}
@media(max-width:760px){.time-grid{grid-template-columns:1fr;gap:22px}}
.bignum{font-family:"Reem Kufi",sans-serif;font-size:clamp(64px,13vw,104px);color:var(--fayrouzi);line-height:1;font-weight:700}
.bignum small{display:block;font-size:16px;color:var(--hibr-2);font-weight:400;margin-top:10px;font-family:"Noto Naskh Arabic",serif}
.ledger{list-style:none;margin:18px 0 0;padding:0;font-size:15.5px;color:var(--hibr-2);max-width:44ch}
.ledger li{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px dashed var(--hd)}
.ledger li:last-child{border:0;color:var(--kohli);font-weight:600}

/* المسارات */
.tracks{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media(max-width:820px){.tracks{grid-template-columns:1fr}}
.track{background:var(--waraq-2);border:1px solid var(--hd);border-radius:var(--r);padding:26px 24px}
.track h3{margin:0 0 4px;font-size:22px;color:var(--kohli)}
.track .wird{color:var(--fayrouzi);font-family:"Reem Kufi",sans-serif;font-size:16px;margin-bottom:16px}
.track dl{margin:0;font-size:15.5px}
.track dt{color:var(--hibr-2);font-size:14px}
.track dd{margin:0 0 12px;color:var(--kohli);font-weight:600}
.track:nth-child(3){border-color:var(--thahabi)}

/* المميزات */
.feat{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
@media(max-width:760px){.feat{grid-template-columns:1fr}}
.f{background:var(--waraq-2);border:1px solid var(--hd);border-radius:var(--r);padding:24px}
.f h3{margin:0 0 8px;font-size:19px;color:var(--kohli)}
.f p{margin:0;font-size:16px;color:var(--hibr-2)}

/* الأسئلة */
details{background:var(--waraq-2);border:1px solid var(--hd);border-radius:var(--r);margin-bottom:10px;overflow:hidden}
summary{
  cursor:pointer;padding:18px 22px;font-family:"Reem Kufi",sans-serif;font-size:17.5px;
  color:var(--kohli);list-style:none;display:flex;justify-content:space-between;gap:14px;align-items:center
}
summary::-webkit-details-marker{display:none}
summary::after{content:"+";color:var(--fayrouzi);font-size:24px;line-height:1}
details[open] summary::after{content:"–"}
details .body{padding:0 22px 20px;color:var(--hibr-2);font-size:16px}

/* الاستمارة */
.form-sec{background:var(--kohli);color:#fff}
.form-sec .sec-t{color:#fff}
.form-sec .sec-s{color:#A9BAC8}
.card{background:var(--waraq-2);border-radius:18px;padding:34px 30px;color:var(--hibr);max-width:660px}
@media(max-width:600px){.card{padding:26px 20px}}
.pledge{
  background:#EAF3F2;border-inline-start:4px solid var(--fayrouzi);
  border-radius:10px;padding:14px 18px;font-size:15.5px;color:#12403F;margin-bottom:26px
}
.field{margin-bottom:18px}
label{display:block;font-family:"Reem Kufi",sans-serif;font-size:15.5px;margin-bottom:7px;color:var(--kohli)}
.req{color:#B03A2E}
input,select,textarea{
  width:100%;padding:13px 15px;border:1.5px solid var(--hd);border-radius:10px;
  font-family:inherit;font-size:16px;background:#FCFCFB;color:var(--hibr)
}
input:focus,select:focus,textarea:focus{border-color:var(--fayrouzi);outline:none;box-shadow:0 0 0 3px rgba(14,124,123,.13)}
textarea{min-height:86px;resize:vertical}
.hint{font-size:13.5px;color:var(--hibr-2);margin-top:5px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:560px){.two{grid-template-columns:1fr}}
.check{display:flex;gap:11px;align-items:flex-start;font-size:15px;color:var(--hibr-2);line-height:1.75}
.check input{width:19px;height:19px;flex:none;margin-top:6px;accent-color:var(--fayrouzi)}
.submit{width:100%;margin-top:8px;font-size:18px;padding:16px}
.submit[disabled]{opacity:.55;cursor:not-allowed}
.err{color:#B03A2E;font-size:14.5px;margin-top:10px;display:none}
.done{display:none;text-align:center;padding:14px 0}
.done h3{color:var(--fayrouzi-d);font-size:24px;margin:0 0 10px}
.done p{color:var(--hibr-2)}
.wa{display:inline-block;background:#1F9E52;color:#fff;text-decoration:none;padding:14px 28px;border-radius:var(--r);font-family:"Reem Kufi",sans-serif;margin-top:12px}

/* إشعار الفتح */
.notice{
  position:fixed;inset-inline:16px;bottom:16px;z-index:60;max-width:430px;margin-inline:auto;
  background:var(--waraq-2);border:1px solid var(--hd);border-inline-start:5px solid var(--thahabi);
  border-radius:var(--r);padding:18px 20px;box-shadow:0 16px 42px rgba(22,38,63,.22);
  transform:translateY(140%);transition:transform .4s cubic-bezier(.2,.8,.2,1)
}
.notice.in{transform:none}
.notice p{font-size:15.5px;margin:0 0 12px;color:var(--hibr)}
.notice strong{color:var(--kohli)}
.notice button{
  background:none;border:0;color:var(--fayrouzi-d);font-family:"Reem Kufi",sans-serif;
  font-size:15.5px;cursor:pointer;padding:0;text-decoration:underline
}
@media(prefers-reduced-motion:reduce){.notice{transition:none}}

/* التذييل */
footer{background:#101D30;color:#93A5B5;padding:46px 0 34px;font-size:15px}
footer a{color:#C7D4DE;text-decoration:none;display:block;padding:4px 0}
footer a:hover{color:#fff;text-decoration:underline}
.fgrid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:34px}
@media(max-width:760px){.fgrid{grid-template-columns:1fr;gap:26px}}
.fgrid h4{font-family:"Reem Kufi",sans-serif;color:#fff;font-size:16px;margin:0 0 10px;font-weight:500}
.fine{border-top:1px solid #223449;margin-top:32px;padding-top:20px;font-size:13.5px;color:#7D8FA0}

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
