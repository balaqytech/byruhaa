@php
    $eventUrl = $event instanceof App\Models\Event ? route('events.show', $event) : route('events.index');
    $registrationUrl = $event instanceof App\Models\Event ? route('customer.events.show', $event) : route('events.index');
    $affiliateUrl = route('affiliate.register');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>برنامج «مهاجر إلى ربي» — بيرحاء · إبراء · صيف ٢٠٢٦</title>
<meta name="description" content="٣٠ يوماً منظّمة بالدقيقة لابنك في قلب الطبيعة — قرآن كريم، نحوٌ بالفطرة، ومهارات الحياة، بقيادة أبي بلج وثلاثة أعلام. مقاعد محدودة لطلاب الصفوف ٧–٩ | إبراء، عُمان">
<script>document.documentElement.className='js';</script>

<!--
  ================================================================
  صفحة «مهاجر إلى ربي» — نسخة محدّثة جاهزة للمبرّم
  المعدّل/الجديد موسومٌ بكلمتَي «جديد» و«معدّل» داخل التعليقات.
  ألوان الهوية: كحلي 16263F · فيروزي 0E7C7B · ذهبي B7892B
  ================================================================
-->

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Reem+Kufi:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Reem+Kufi:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap"></noscript>

@verbatim
<style>
:root{
  --navy:#16263F;        /* كحلي */
  --navy-2:#1E3252;
  --teal:#0E7C7B;        /* فيروزي */
  --teal-soft:#13938F;
  --gold:#B7892B;        /* ذهبي */
  --gold-soft:#D2A646;
  --sand:#F6F3EC;
  --cream:#FBF9F4;
  --ink:#23303f;
  --muted:#5e6b78;
  --line:rgba(22,38,63,.10);
  --display:"Reem Kufi","Geeza Pro","Segoe UI",Tahoma,system-ui,sans-serif;
  --body:"Noto Naskh Arabic","Geeza Pro","Segoe UI",Tahoma,system-ui,serif;
  --amiri:"Amiri","Noto Naskh Arabic","Geeza Pro",serif;
  --shadow:0 18px 50px -22px rgba(22,38,63,.45);
  --radius:20px;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:var(--body);
  color:var(--ink);
  background:var(--cream);
  line-height:1.9;
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;
}
h1,h2,h3,h4,.disp{font-family:var(--display);line-height:1.4;font-weight:600;color:var(--navy)}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
.wrap{max-width:1160px;margin-inline:auto;padding-inline:22px}
section{padding:84px 0;position:relative}
.eyebrow{
  display:inline-block;font-family:var(--display);font-weight:600;
  color:var(--teal);letter-spacing:.5px;font-size:.95rem;
  padding:6px 16px;border:1px solid rgba(14,124,123,.28);border-radius:999px;
  background:rgba(14,124,123,.06);margin-bottom:18px;
}
.sec-title{font-size:clamp(1.7rem,3.4vw,2.6rem);margin-bottom:14px}
.sec-sub{color:var(--muted);max-width:680px;font-size:1.06rem}
.center{text-align:center}
.center .sec-sub{margin-inline:auto}

/* أزرار */
.btn{
  display:inline-flex;align-items:center;gap:10px;justify-content:center;
  font-family:var(--display);font-weight:600;font-size:1.05rem;
  padding:15px 30px;border-radius:999px;cursor:pointer;border:0;
  transition:transform .25s ease, box-shadow .25s ease, background .25s ease;
}
.btn-gold{background:linear-gradient(135deg,var(--gold-soft),var(--gold));color:#fff;box-shadow:0 14px 30px -12px rgba(183,137,43,.7)}
.btn-gold:hover{transform:translateY(-3px);box-shadow:0 20px 40px -12px rgba(183,137,43,.8)}
.btn-ghost{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.5)}
.btn-ghost:hover{background:rgba(255,255,255,.12);transform:translateY(-3px)}
.btn-teal{background:linear-gradient(135deg,var(--teal-soft),var(--teal));color:#fff;box-shadow:0 14px 30px -12px rgba(14,124,123,.6)}
.btn-teal:hover{transform:translateY(-3px)}

/* ════════ جديد: الشريط العاجل العلوي ════════ */
.topbar{
  position:sticky;top:0;z-index:60;
  background:linear-gradient(90deg,var(--navy),var(--navy-2));
  color:#fff;border-bottom:1px solid rgba(183,137,43,.4);
}
.topbar .wrap{display:flex;align-items:center;justify-content:center;gap:18px;
  padding:9px 22px;flex-wrap:wrap;font-family:var(--display);font-size:.96rem}
.topbar .dot{width:8px;height:8px;border-radius:50%;background:var(--gold-soft);
  box-shadow:0 0 0 0 rgba(210,166,70,.7);animation:pulse 2s infinite}
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(210,166,70,.6)}70%{box-shadow:0 0 0 9px rgba(210,166,70,0)}100%{box-shadow:0 0 0 0 rgba(210,166,70,0)}}
.topbar .mini-count{color:var(--gold-soft);font-weight:700}
.topbar a.mini-cta{margin-inline-start:auto;background:var(--gold);color:#fff;
  padding:6px 16px;border-radius:999px;font-weight:600;font-size:.9rem;white-space:nowrap}
@media(max-width:720px){.topbar a.mini-cta{margin-inline-start:0}}

/* ════════ الترويسة ════════ */
.nav{position:absolute;top:0;left:0;right:0;z-index:40}
.nav .wrap{display:flex;align-items:center;justify-content:space-between;padding-top:18px;padding-bottom:18px}
.nav .logo{height:46px;filter:brightness(0) invert(1);opacity:.96}
.nav ul{display:flex;gap:26px;list-style:none;font-family:var(--display);font-weight:500}
.nav ul a{color:rgba(255,255,255,.9);font-size:1rem;transition:color .2s}
.nav ul a:hover{color:var(--gold-soft)}
@media(max-width:860px){.nav ul{display:none}}

/* ════════ الهيرو ════════ */
.hero{
  position:relative;color:#fff;text-align:center;overflow:hidden;
  background:
    radial-gradient(1200px 600px at 70% -10%, rgba(14,124,123,.35), transparent 60%),
    radial-gradient(900px 500px at 10% 10%, rgba(183,137,43,.22), transparent 55%),
    linear-gradient(160deg,#0f1d31 0%, var(--navy) 55%, #122742 100%);
  padding:150px 0 90px;
}
.hero::after{content:"";position:absolute;inset:0;
  background-image:radial-gradient(rgba(255,255,255,.05) 1px,transparent 1px);
  background-size:26px 26px;opacity:.5;pointer-events:none}
.hero .wrap{position:relative;z-index:2}
.hero .kicker{font-family:var(--display);color:var(--gold-soft);letter-spacing:2px;font-size:1rem;margin-bottom:14px}
.hero h1{color:#fff;font-size:clamp(2.4rem,6vw,4.2rem);font-weight:700;line-height:1.25;margin-bottom:6px}
.hero h1 .by{display:block;font-size:clamp(1rem,2.2vw,1.25rem);color:rgba(255,255,255,.78);font-weight:400;margin-top:14px}
.hero .lead{font-family:var(--amiri);font-size:clamp(1.5rem,3.2vw,2.05rem);color:var(--gold-soft);margin:22px 0 10px}
.hero p.desc{max-width:720px;margin:0 auto 30px;color:rgba(255,255,255,.86);font-size:1.12rem}
.hero-stats{display:flex;justify-content:center;gap:14px;flex-wrap:wrap;margin:6px 0 34px}
.stat{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);
  border-radius:16px;padding:16px 22px;min-width:120px;backdrop-filter:blur(6px)}
.stat b{display:block;font-family:var(--display);font-size:1.9rem;color:var(--gold-soft);font-weight:700}
.stat span{font-size:.92rem;color:rgba(255,255,255,.82)}
.hero-cta{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}

/* ════════ جديد: بطاقة العدّاد التنازلي ════════ */
.countdown-card{
  max-width:720px;margin:40px auto 0;
  background:rgba(255,255,255,.07);border:1px solid rgba(183,137,43,.4);
  border-radius:var(--radius);padding:26px 24px;backdrop-filter:blur(8px);
}
.countdown-card .cd-label{font-family:var(--display);color:#fff;font-size:1.08rem;margin-bottom:6px}
.countdown-card .cd-note{color:var(--gold-soft);font-size:.95rem;margin-bottom:18px}
.countdown{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}
.cd-unit{background:rgba(0,0,0,.22);border:1px solid rgba(255,255,255,.12);border-radius:14px;
  padding:14px 8px;min-width:84px}
.cd-unit b{display:block;font-family:var(--display);font-size:2.1rem;font-weight:700;color:#fff;line-height:1}
.cd-unit span{font-size:.85rem;color:rgba(255,255,255,.7);margin-top:6px;display:block}
.seats-pill{display:inline-flex;align-items:center;gap:10px;margin-top:20px;
  background:rgba(183,137,43,.16);border:1px solid rgba(183,137,43,.5);color:#fff;
  padding:10px 20px;border-radius:999px;font-family:var(--display);font-size:1rem}
.seats-pill b{color:var(--gold-soft)}
.seats-pill .live{width:9px;height:9px;border-radius:50%;background:#ff6b6b;animation:pulse 1.8s infinite}

/* ════════ بطاقات القلق ════════ */
.worry{background:var(--sand)}
.worry-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:40px}
.worry-card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);
  padding:32px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.worry-card::before{content:"";position:absolute;inset-inline-start:0;top:0;bottom:0;width:5px;
  background:linear-gradient(var(--gold),var(--teal))}
.worry-card h3{font-size:1.35rem;margin-bottom:12px}
.worry-card p{color:var(--muted);margin-bottom:14px}
.worry-card .sol{background:rgba(14,124,123,.08);border-radius:12px;padding:14px 16px;
  color:var(--navy);font-weight:500;border:1px solid rgba(14,124,123,.18)}
.worry-card .sol b{color:var(--teal)}
@media(max-width:760px){.worry-grid{grid-template-columns:1fr}}

/* ════════ جديد: ثلاثة أعلام ════════ */
.masters{background:linear-gradient(170deg,#0f1d31,var(--navy));color:#fff}
.masters .sec-title{color:#fff}
.masters .sec-sub{color:rgba(255,255,255,.82)}
.masters .eyebrow{color:var(--gold-soft);border-color:rgba(183,137,43,.4);background:rgba(183,137,43,.1)}
.masters-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:46px}
.master{
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);
  border-radius:var(--radius);padding:30px 26px;transition:transform .3s ease,border-color .3s ease;
  display:flex;flex-direction:column}
.master:hover{transform:translateY(-6px);border-color:rgba(183,137,43,.55)}
.master .axis{font-family:var(--display);font-size:.9rem;color:var(--gold-soft);
  letter-spacing:.5px;margin-bottom:18px}
.master .medallion{
  width:96px;height:96px;border-radius:50%;margin-bottom:20px;
  display:grid;place-items:center;font-family:var(--display);font-weight:700;font-size:2rem;color:#fff;
  background:radial-gradient(circle at 30% 25%,var(--teal-soft),var(--navy-2));
  border:2px solid rgba(183,137,43,.6);
  /* المبرّم: استبدِل بصورة العَلَم الحقيقية عند توفرها */
}
.master h3{color:#fff;font-size:1.3rem;margin-bottom:4px}
.master .role{color:var(--gold-soft);font-size:.98rem;font-family:var(--display);margin-bottom:14px}
.master p{color:rgba(255,255,255,.8);font-size:1rem;flex:1}
.master .tag{margin-top:18px;font-size:.9rem;color:rgba(255,255,255,.62);
  border-top:1px solid rgba(255,255,255,.1);padding-top:14px}
@media(max-width:860px){.masters-grid{grid-template-columns:1fr}}

/* ════════ خمسة أبعاد ════════ */
.features{background:var(--cream)}
.feat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:22px;margin-top:46px}
.feat{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:30px;
  box-shadow:0 14px 40px -28px rgba(22,38,63,.5);transition:transform .3s}
.feat:hover{transform:translateY(-5px)}
.feat .num{font-family:var(--display);font-weight:700;color:var(--gold);font-size:1.05rem;margin-bottom:8px}
.feat h3{font-size:1.22rem;margin-bottom:12px;color:var(--navy)}
.feat p{color:var(--muted);font-size:1rem;margin-bottom:14px}
.feat .quote{font-family:var(--amiri);font-size:1.02rem;color:var(--teal);
  background:rgba(14,124,123,.06);border-radius:12px;padding:12px 14px;border-inline-start:3px solid var(--teal)}

/* ════════ خماسية السكينة ════════ */
.sakina{background:var(--navy);color:#fff}
.sakina .sec-title{color:#fff}
.sakina .sec-sub{color:rgba(255,255,255,.8)}
.sakina .eyebrow{color:var(--gold-soft);border-color:rgba(183,137,43,.4);background:rgba(183,137,43,.1)}
.penta{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-top:46px}
.penta-col{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);
  border-radius:16px;padding:24px 18px;transition:background .3s}
.penta-col:hover{background:rgba(14,124,123,.18)}
.penta-col h3{color:var(--gold-soft);font-size:1.18rem;margin-bottom:16px;text-align:center}
.penta-col ul{list-style:none}
.penta-col li{color:rgba(255,255,255,.82);font-size:.96rem;padding:7px 0;
  border-bottom:1px dashed rgba(255,255,255,.1)}
.penta-col li:last-child{border:0}
@media(max-width:920px){.penta{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.penta{grid-template-columns:1fr}}

/* ════════ يوم في البرنامج ════════ */
.day{background:var(--sand)}
.timeline{margin-top:46px;border-inline-start:2px solid rgba(14,124,123,.3);
  padding-inline-start:26px;max-width:760px}
.tl-item{position:relative;padding:14px 0}
.tl-item::before{content:"";position:absolute;inset-inline-start:-33px;top:22px;
  width:12px;height:12px;border-radius:50%;background:var(--gold);border:2px solid var(--cream)}
.tl-item .time{font-family:var(--display);color:var(--teal);font-weight:600;font-size:1.02rem}
.tl-item .act{color:var(--ink)}
.weekend{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-top:40px}
.wcard{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:26px;box-shadow:var(--shadow)}
.wcard h3{color:var(--teal);font-size:1.25rem;margin-bottom:14px}
.wcard ul{list-style:none}
.wcard li{padding:6px 0;color:var(--muted);padding-inline-start:22px;position:relative}
.wcard li::before{content:"◆";position:absolute;inset-inline-start:0;color:var(--gold);font-size:.7rem;top:9px}
@media(max-width:760px){.weekend{grid-template-columns:1fr}}

/* ════════ ما بعد البرنامج ════════ */
.after{background:var(--cream)}
.after-card{display:grid;grid-template-columns:1.1fr 1fr;gap:40px;align-items:center;
  background:linear-gradient(150deg,#fff,#f3efe6);border:1px solid var(--line);
  border-radius:28px;padding:44px;box-shadow:var(--shadow);margin-top:30px}
.after-card h3{font-size:1.7rem;margin-bottom:16px;color:var(--navy)}
.after-card p{color:var(--muted);margin-bottom:18px}
.after-card .chips{display:flex;gap:10px;flex-wrap:wrap}
.after-card .chips span{background:rgba(14,124,123,.1);color:var(--teal);font-family:var(--display);
  font-size:.9rem;padding:8px 14px;border-radius:999px;border:1px solid rgba(14,124,123,.2)}
.after-img{border-radius:20px;overflow:hidden;box-shadow:var(--shadow)}
@media(max-width:820px){.after-card{grid-template-columns:1fr;padding:30px}}

/* ════════ جديد: الرسوم والعروض ════════ */
.pricing{background:var(--navy);color:#fff}
.pricing .sec-title{color:#fff}
.pricing .eyebrow{color:var(--gold-soft);border-color:rgba(183,137,43,.4);background:rgba(183,137,43,.1)}
.price-deadline{display:inline-flex;align-items:center;gap:10px;margin-top:14px;
  background:rgba(255,107,107,.14);border:1px solid rgba(255,107,107,.45);color:#ffd9d9;
  padding:9px 20px;border-radius:999px;font-family:var(--display);font-size:.98rem}
.price-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:42px}
.price-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.14);
  border-radius:18px;padding:28px 22px;text-align:center;position:relative;transition:transform .3s}
.price-card:hover{transform:translateY(-6px)}
.price-card.feat-card{border-color:var(--gold);background:rgba(183,137,43,.12)}
.price-card .ribbon{position:absolute;top:-12px;inset-inline-end:18px;background:var(--gold);
  color:#fff;font-family:var(--display);font-size:.78rem;padding:4px 12px;border-radius:999px}
.price-card .plan{font-family:var(--display);color:var(--gold-soft);font-size:1.05rem;margin-bottom:10px}
.price-card .amount{font-family:var(--display);font-size:2.5rem;font-weight:700;color:#fff;line-height:1}
.price-card .amount small{font-size:1rem;color:rgba(255,255,255,.7);font-weight:400}
.price-card .full{color:rgba(255,255,255,.55);text-decoration:line-through;font-size:1rem;margin-top:8px}
.price-card .save{color:#7CE7C5;font-size:.95rem;margin-top:6px}
.price-card .cond{color:rgba(255,255,255,.7);font-size:.9rem;margin-top:12px;min-height:40px}
@media(max-width:920px){.price-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.price-grid{grid-template-columns:1fr}}

/* ════════ جديد: سوّق واربح ════════ */
.affiliate{background:linear-gradient(160deg,var(--teal),#0a5e5d);color:#fff}
.affiliate .sec-title{color:#fff}
.aff-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:42px}
.aff-card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);
  border-radius:var(--radius);padding:32px}
.aff-card h3{color:#fff;font-size:1.3rem;margin-bottom:12px;display:flex;align-items:center;gap:10px}
.aff-card .big{font-family:var(--display);font-size:2.4rem;font-weight:700;color:var(--gold-soft);margin:8px 0}
.aff-card p{color:rgba(255,255,255,.88);margin-bottom:8px}
.aff-card ul{list-style:none;margin-top:12px}
.aff-card li{padding:6px 0 6px 24px;position:relative;color:rgba(255,255,255,.9)}
.aff-card li::before{content:"✦";position:absolute;inset-inline-start:0;color:var(--gold-soft)}
.aff-cta{text-align:center;margin-top:36px}
@media(max-width:760px){.aff-grid{grid-template-columns:1fr}}

/* ════════ الاستمارة ════════ */
.register{background:var(--sand)}
.reg-box{display:grid;grid-template-columns:1fr 1fr;gap:0;border-radius:28px;overflow:hidden;
  box-shadow:var(--shadow);margin-top:30px;border:1px solid var(--line)}
.reg-info{background:linear-gradient(160deg,var(--navy),var(--navy-2));color:#fff;padding:46px}
.reg-info h3{color:#fff;font-size:1.6rem;margin-bottom:16px}
.reg-info p{color:rgba(255,255,255,.82);margin-bottom:22px}
.reg-info .point{display:flex;gap:12px;margin-bottom:14px;align-items:flex-start}
.reg-info .point i{color:var(--gold-soft);font-style:normal;font-size:1.2rem}
.reg-info .point span{color:rgba(255,255,255,.9)}
.reg-form{background:#fff;padding:46px}
.reg-form label{display:block;font-family:var(--display);font-weight:500;color:var(--navy);margin:0 0 6px;font-size:.96rem}
.reg-form .field{margin-bottom:18px}
.reg-form input,.reg-form select{width:100%;padding:13px 16px;border:1.5px solid var(--line);
  border-radius:12px;font-family:var(--body);font-size:1rem;background:var(--cream);transition:border-color .2s}
.reg-form input:focus,.reg-form select:focus{outline:none;border-color:var(--teal)}
.reg-form .btn{width:100%;margin-top:6px}
.reg-form .micro{text-align:center;color:var(--muted);font-size:.88rem;margin-top:14px}
@media(max-width:820px){.reg-box{grid-template-columns:1fr}.reg-info,.reg-form{padding:32px}}

/* ════════ الأسئلة الشائعة ════════ */
.faq{background:var(--cream)}
.faq-list{max-width:820px;margin:40px auto 0}
.faq-cat{font-family:var(--display);color:var(--gold);font-size:1.05rem;margin:28px 0 12px;
  border-bottom:1px solid var(--line);padding-bottom:8px}
details{background:#fff;border:1px solid var(--line);border-radius:14px;margin-bottom:12px;overflow:hidden}
details summary{cursor:pointer;padding:18px 22px;font-family:var(--display);font-weight:500;
  color:var(--navy);list-style:none;display:flex;justify-content:space-between;gap:14px;align-items:center}
details summary::-webkit-details-marker{display:none}
details summary::after{content:"+";color:var(--teal);font-size:1.5rem;transition:transform .25s}
details[open] summary::after{transform:rotate(45deg)}
details .ans{padding:0 22px 20px;color:var(--muted)}
details .ans b{color:var(--navy)}

/* ════════ التذييل ════════ */
.foot{background:var(--navy);color:#fff;text-align:center;padding:56px 0 30px}
.foot img{height:54px;margin:0 auto 20px;filter:brightness(0) invert(1);opacity:.9}
.foot h3{color:#fff;font-size:1.4rem;margin-bottom:8px}
.foot p{color:rgba(255,255,255,.7);margin-bottom:6px}
.foot .copy{margin-top:24px;border-top:1px solid rgba(255,255,255,.1);padding-top:20px;
  color:rgba(255,255,255,.5);font-size:.9rem}

/* زر واتساب عائم */
.wa{position:fixed;inset-block-end:22px;inset-inline-start:22px;z-index:55;
  background:#25D366;color:#fff;border-radius:999px;padding:13px 22px;font-family:var(--display);
  font-weight:600;box-shadow:0 12px 30px -8px rgba(37,211,102,.6);display:flex;align-items:center;gap:10px;
  transition:transform .25s}
.wa:hover{transform:translateY(-3px)}

/* كشف عند التمرير */
.js .reveal{opacity:0;transform:translateY(26px);transition:opacity .7s ease, transform .7s ease}
.reveal.in{opacity:1;transform:none}

@media(prefers-reduced-motion:reduce){
  *{animation:none!important;scroll-behavior:auto!important}
  .reveal,.js .reveal{opacity:1!important;transform:none!important;transition:none!important}
}
</style>
@endverbatim
</head>
<body>

<!-- ════════ جديد: الشريط العاجل العلوي ════════ -->
<div class="topbar">
  <div class="wrap">
    <span class="dot"></span>
    <span>تنطلق الدفعة الأولى يوم <strong>١ يوليو</strong> — ويُغلق الالتحاق بانطلاقها</span>
    <span class="mini-count" id="miniCount">— — —</span>
    <a class="mini-cta" href="#register">احجز مقعدك</a>
  </div>
</div>

<!-- ════════ الهيرو ════════ -->
<header class="hero">
  <nav class="nav">
    <div class="wrap">
      <a href="https://byruhaa.com"><img class="logo" src="https://byruhaa.com/wp-content/uploads/2023/02/باللون-الأبيض-شعار-بيرحاء-الجديد-0٤-scaled.png" alt="بيرحاء"></a>
      <ul>
        <li><a href="#masters">الأعلام الثلاثة</a></li>
        <li><a href="#program">البرنامج</a></li>
        <li><a href="#pricing">الرسوم</a></li>
        <li><a href="#affiliate">سوّق واربح</a></li>
        <li><a href="#faq">الأسئلة</a></li>
      </ul>
    </div>
  </nav>

  <div class="wrap">
    <div class="kicker">مخيم بيرحاء · إبراء · سلطنة عُمان · صيف ٢٠٢٦</div>
    <h1>برنامج «مهاجر إلى ربي»
      <span class="by">بقيادة أبي بلج عبدالله العيسري</span>
    </h1>
    <div class="lead">٣٠ يوماً تصنع فارقاً يدوم عمراً</div>
    <p class="desc">تجربةٌ تعليميةٌ مكثَّفة في قلب الطبيعة — تجمع القرآن الكريم، والنحوَ بالفطرة، ومهاراتِ الحياة، وتُحضِّر الجيل لمستقبلٍ يتغيّر بسرعة البرق — في بيئةٍ آمنةٍ ملهمة.</p>

    <div class="hero-stats">
      <div class="stat"><b>٣٠</b><span>يوماً متصلاً</span></div>
      <div class="stat"><b>٥٠</b><span>مقعداً فقط</span></div>
      <div class="stat"><b>٧–٩</b><span>الصفوف</span></div>
      <div class="stat"><b>٣</b><span>أعلامٍ يقودون</span></div>
    </div>

    <div class="hero-cta">
      <a href="#register" class="btn btn-gold">احجز مقعدك الآن</a>
      <a href="#program" class="btn btn-ghost">اكتشف البرنامج</a>
    </div>

    <!-- ════════ جديد: العدّاد التنازلي ════════ -->
    <div class="countdown-card reveal">
      <div class="cd-label">يُغلق باب الالتحاق بالدفعة الأولى خلال</div>
      <div class="cd-note">والتسجيل المبكر — بعروضه — ينتهي ٣٠ يونيو</div>
      <div class="countdown" id="countdown">
        <div class="cd-unit"><b data-d>٠</b><span>يوم</span></div>
        <div class="cd-unit"><b data-h>٠</b><span>ساعة</span></div>
        <div class="cd-unit"><b data-m>٠</b><span>دقيقة</span></div>
        <div class="cd-unit"><b data-s>٠</b><span>ثانية</span></div>
      </div>
      <!-- المبرّم: عدّل قيمة seatsLeft في السكربت أسفل الصفحة -->
      <div class="seats-pill"><span class="live"></span> المقاعد المتبقّية محدودة — <b id="seatsLeft">٠</b> مقعداً</div>
    </div>
  </div>
</header>

<!-- ════════ نفهم قلقك ════════ -->
<section class="worry">
  <div class="wrap center">
    <span class="eyebrow">نفهم قلقك</span>
    <h2 class="sec-title">أنتَ لستَ وحدك في هذا القلق</h2>
    <p class="sec-sub">خوفان يشغلان كلّ ولي أمرٍ يُحبّ أبناءه — وبرنامجنا مُصمَّمٌ كاملاً ليُجيب عليهما.</p>
    <div class="worry-grid" style="text-align:start">
      <div class="worry-card reveal">
        <h3>خوف السفر في زمن الاضطرابات</h3>
        <p>المشهد الدولي متقلّب، والسفر بات حِملاً ثقيلاً على الأسر. برنامجنا في إبراء — على أرضك، تحت سمائك، بعيداً عن مخاطر الخارج.</p>
        <div class="sol"><b>الحل:</b> تجربةٌ استثنائية داخل عُمان — الأمان الكامل مع المنهج الثريّ.</div>
      </div>
      <div class="worry-card reveal">
        <h3>خوف بقاء الأبناء دون إشراف</h3>
        <p>ثلاثة أشهرٍ إجازة، والابن أمام الشاشة طوال اليوم. لا توجيه، لا إنتاج، لا بناء — هذا ليس راحةً، بل إهدارٌ لأثمن مراحل التكوين.</p>
        <div class="sol"><b>الحل:</b> ٣٠ يوماً منظّمة بالدقيقة — إشرافٌ تام، وولي الأمر على علمٍ بكل شيء.</div>
      </div>
    </div>
  </div>
</section>

<!-- ════════ جديد: ثلاثة أعلام تقود الرحلة ════════ -->
<section class="masters" id="masters">
  <div class="wrap center">
    <span class="eyebrow">ما لم تكشفه الصفحة من قبل</span>
    <h2 class="sec-title">ثلاثة أعلامٍ تقود الرحلة</h2>
    <p class="sec-sub">لا برنامجٌ عام — بل ثلاثة محاور، يقود كلّ محورٍ منها عَلَمٌ في فنّه، يرافق القادة طوال الشهر.</p>

    <div class="masters-grid" style="text-align:start">
      <div class="master reveal">
        <div class="axis">المحور الأول · حفظ القرآن</div>
        <div class="medallion">إ.غ</div>
        <h3>الأستاذ إبراهيم الغافري</h3>
        <div class="role">حفظ القرآن بطريقةٍ إبداعية</div>
        <p>مؤسّس برنامج «مكنون» — أشهر برامج حفظ القرآن في عُمان، ومضى عليه نحو عشر سنوات، تخرّج منه مئاتُ الحُفّاظ المُتقنين.</p>
        <div class="tag">يُشرف على ملف القرآن في البرنامج</div>
      </div>

      <div class="master reveal">
        <div class="axis">المحور الثاني · النحو العربي</div>
        <div class="medallion">أ.ص</div>
        <h3>د. أحمد صوان</h3>
        <div class="role">النحوُ بالفطرة — تنظيراً وتطبيقاً</div>
        <p>تلميذ د. عبدالله الدنّان ووريثه الأول في تعليم العربية الفصيحة بالفطرة، حائزُ جائزة الشيخ خليفة بن زايد في قصص الأطفال، ومحاضرٌ في جامعات سوريا ومصر وتركيا. يرافق القادة الشهرَ كاملاً.</p>
        <div class="tag">يُلازم القادة طوال الشهر — بإذن الله</div>
      </div>

      <div class="master reveal">
        <div class="axis">المحور الثالث · مهارات الحياة</div>
        <div class="medallion">أ.ب</div>
        <h3>أبو بلج عبدالله العيسري</h3>
        <div class="role">خماسية السكينة — تنظيراً وتطبيقاً</div>
        <p>يَنقل عُصارةَ خبرةٍ تتجاوز رُبع قرن في تعليم «خماسية السكينة» وما يتصل بها، استفاد من برامجه أكثرُ من ٤٠٬٠٠٠ منتسب.</p>
        <div class="tag">يقود البرنامج ويُشرف على مهارات الحياة</div>
      </div>
    </div>
  </div>
</section>

<!-- ════════ خمسة أبعاد ════════ -->
<section class="features" id="program">
  <div class="wrap center">
    <span class="eyebrow">ما يميّزنا</span>
    <h2 class="sec-title">خمسة أبعادٍ تجعل البرنامج استثناءً حقيقياً</h2>
    <p class="sec-sub">بُنيت من خبرةٍ ميدانيةٍ متراكمة ومن دراسات OECD ومنتدى الاقتصاد العالمي في مهارات المستقبل.</p>
    <div class="feat-grid" style="text-align:start">
      <div class="feat reveal"><div class="num">١ — الأصل والمرساة</div><h3>القرآن والحياة في برنامجٍ واحدٍ متكامل</h3><p>يُقدَّم القرآن مادةً مستقلةً بأوجه التعامل الستة: الاستماع، والحفظ، والتلاوة، والتدبّر، والامتثال، والتبليغ — وهو في الوقت ذاته النورُ الذي يَغشى كلّ شيء.</p><div class="quote">لسنا نُعلِّم القرآن ثم الحياة — بل نُعلِّمهما معاً بلا فصل.</div></div>
      <div class="feat reveal"><div class="num">٢ — منهجية موثّقة</div><h3>تحضير الجيل للمستقبل بأدواتٍ حقيقية</h3><p>٣٩٪ من مهارات اليوم ستتغيّر جذرياً بحلول ٢٠٣٠. نُترجم ذلك إلى منهجٍ يوميّ يعيشه الطالب — التفكير النقدي، والإبداع، والتكيّف، والذكاء الاصطناعي.</p><div class="quote">«المستقبل لن يكافئ أصحاب الشهادات — بل أصحاب المهارات التي تتكيّف» — WEF 2025</div></div>
      <div class="feat reveal"><div class="num">٣ — علم نفس تطبيقي</div><h3>بناء الهوية في سنّ الهشاشة بأمانٍ حقيقي</h3><p>الصفوف ٧–٩ مرحلةُ الهوية الأكثر حساسية. نضع هذه الهشاشة في صميم التصميم: جلساتٌ حوارية، ومهاراتُ ضبط النفس، وبيئةٌ آمنة يكتشف فيها الطالب نفسه دون خوف.</p><div class="quote">McKinsey: المهارات الاجتماعية والعاطفية بين أعلى المهارات طلباً حتى ٢٠٣٠.</div></div>
      <div class="feat reveal"><div class="num">٤ — تجربة ريادية</div><h3>مشروعٌ حقيقيّ ينتهي بنتاجٍ ملموس</h3><p>كلّ طالبٍ يُغادر بمشروعٍ شخصيٍّ طوّره خلال الشهر: فكرة، خطة، وعرضٌ أمام أقرانه ومعلّميه — تدريبٌ على التفكير الريادي الذي يُعدّه OECD ركيزةً لوظائف المستقبل.</p><div class="quote">التعلّم بالمشاريع يرفع الاحتفاظ بالمهارات ٧٥٪ مقارنةً بالتلقين.</div></div>
      <div class="feat reveal"><div class="num">٥ — استثمار العمر</div><h3>برنامج الاستمرارية السنوي — البدايةُ لا النهاية</h3><p>بعد الشهر، يدخل الطالب برنامجاً يمتدّ العامَ كلّه: جلساتٌ أسبوعية عن بُعد، ومتابعةٌ شهرية، ولقاءاتٌ فصلية حضورية تُرسِّخ التغيير.</p><div class="quote">من تجربةٍ صيفية — إلى مجتمع تعلّمٍ مستدام يرافق الطالب عاماً كاملاً.</div></div>
    </div>
  </div>
</section>

<!-- ════════ خماسية السكينة ════════ -->
<section class="sakina">
  <div class="wrap center">
    <span class="eyebrow">فلسفة البرنامج</span>
    <h2 class="sec-title">خماسية السكينة</h2>
    <p class="sec-sub">خمسةُ أبعادٍ متوازنة، لكلّ بُعدٍ خمسةُ فروع — كلّ يومٍ في البرنامج مُصمَّمٌ ليلمسها جميعاً.</p>
    <div class="penta">
      <div class="penta-col reveal"><h3>١ — عبادة</h3><ul><li>الإيمان</li><li>الإحسان</li><li>قول الحسن</li><li>الصلاة</li><li>الإنفاق</li></ul></div>
      <div class="penta-col reveal"><h3>٢ — علم</h3><ul><li>القرآن</li><li>البيان</li><li>الشرعية والكونية</li><li>القراءة</li><li>الكتابة</li></ul></div>
      <div class="penta-col reveal"><h3>٣ — عمل</h3><ul><li>الزراعة</li><li>التجارة</li><li>التثمير والادخار</li><li>العمل المنزلي</li><li>التطوّع</li></ul></div>
      <div class="penta-col reveal"><h3>٤ — لعب</h3><ul><li>الرتع</li><li>ألعاب المحاكاة</li><li>الألعاب الحركية</li><li>الألعاب التقنية</li><li>الألعاب التمثيلية</li></ul></div>
      <div class="penta-col reveal"><h3>٥ — نوم وصحة</h3><ul><li>قبل النوم</li><li>النوم</li><li>بعد الاستيقاظ</li><li>الغذاء</li><li>الصحة</li></ul></div>
    </div>
  </div>
</section>

<!-- ════════ يوم في البرنامج ════════ -->
<section class="day">
  <div class="wrap center">
    <span class="eyebrow">نموذج الأيام</span>
    <h2 class="sec-title">يومٌ في «مهاجر إلى ربي»</h2>
    <p class="sec-sub">كلّ ساعةٍ مُصمَّمةٌ باتزان — لا مللَ ولا إرهاقَ ولا إهدارَ للوقت.</p>
  </div>
  <div class="wrap">
    <div class="timeline">
      <div class="tl-item"><div class="time">٠٣:٢٠ فجراً</div><div class="act">التهجّد</div></div>
      <div class="tl-item"><div class="time">٠٤:٠٠ — ٠٦:٠٠</div><div class="act">قرآن الفجر وأذكار الصباح وصلاة الضحى (مع مراجعة المحفوظ)</div></div>
      <div class="tl-item"><div class="time">٠٦:٠٠ — ٠٦:٣٠</div><div class="act">الرياضة الصباحية</div></div>
      <div class="tl-item"><div class="time">٠٦:٣٠ — ٠٧:٣٠</div><div class="act">إعداد الإفطار وتناوله (بمشاركة القادة)</div></div>
      <div class="tl-item"><div class="time">٠٨:٣٠ — ١١:٣٠</div><div class="act">الحلقات: تعليم القرآن، والبيان، ومهارات الحياة</div></div>
      <div class="tl-item"><div class="time">١١:٣٠ — ١٢:٠٠</div><div class="act">نصف ساعةٍ للتقنية (والذكاء الاصطناعي)</div></div>
      <div class="tl-item"><div class="time">١٢:٠٠ — ٠١:٠٠</div><div class="act">صلاة الظهر</div></div>
      <div class="tl-item"><div class="time">٠١:٠٠ — ٠٢:٠٠</div><div class="act">وجبة الغداء (بمشاركة القادة)</div></div>
      <div class="tl-item"><div class="time">٠٢:٠٠ — ٠٣:٣٠</div><div class="act">وقتٌ مفتوح (نوم · لعب · اتصالٌ بالأهل وفق الضوابط)</div></div>
      <div class="tl-item"><div class="time">٠٤:٠٠ — ٠٤:٣٠</div><div class="act">صلاة العصر جماعةً ومراجعة الحفظ</div></div>
      <div class="tl-item"><div class="time">٠٥:٠٠ — ٠٦:٠٠</div><div class="act">الرياضة المسائية</div></div>
      <div class="tl-item"><div class="time">٠٧:٠٠ — ٠٨:٣٠</div><div class="act">المغرب والعشاء</div></div>
      <div class="tl-item"><div class="time">٠٩:٠٠ — ١٠:٠٠</div><div class="act">وقتٌ مفتوح والاستعداد للنوم</div></div>
      <div class="tl-item"><div class="time">١٠:٠٠ م — ٠٣:٠٠ ص</div><div class="act">النوم الإلزامي (يُمنع: إضاءة، حديث، هواتف)</div></div>
    </div>

    <div class="weekend">
      <div class="wcard reveal"><h3>الجمعة</h3><ul><li>قرآن الصباح وإفطارٌ جماعيّ مميّز</li><li>رحلةٌ استكشافية خارج المخيم</li><li>صلاة الجمعة ونشاطٌ ترفيهيّ جماعي</li><li>عشاءٌ في الهواء الطلق</li></ul></div>
      <div class="wcard reveal"><h3>السبت</h3><ul><li>برنامج مغامرةٍ أو زيارةٍ ميدانية</li><li>ورشةٌ تطبيقية من اختيار الطلاب</li><li>وقتٌ حرٌّ ممتد واتصالٌ بالأهل</li><li>سهرةٌ ختامية: قصصٌ وأناشيدُ وتكريم</li></ul></div>
    </div>
  </div>
</section>

<!-- ════════ ما بعد البرنامج ════════ -->
<section class="after">
  <div class="wrap center">
    <span class="eyebrow">ما بعد البرنامج</span>
    <h2 class="sec-title">الثلاثون يوماً بدايةٌ — ليست نهاية</h2>
  </div>
  <div class="wrap">
    <div class="after-card reveal">
      <div>
        <h3>برنامج «مهاجر إلى ربي» السنوي</h3>
        <p>لأنّ التغيير الحقيقي يحتاج أكثر من ثلاثين يوماً، يلتحق كلّ خريجٍ ببرنامجٍ سنويّ مكمّل يرافقه طوال العام الدراسي — ليس درساً إضافياً، بل مجتمعَ تعلّمٍ يُبقي الروابط حيّةً والهممَ مشتعلة.</p>
        <div class="chips">
          <span>جلسات أسبوعية عن بُعد</span><span>متابعة شهرية للمشروع</span>
          <span>لقاءات فصلية حضورية</span><span>مجموعة أولياء الأمور</span><span>معسكر ختامي سنوي</span>
        </div>
      </div>
      <div class="after-img">
        <img src="https://byruhaa.com/wp-content/uploads/2026/03/ChatGPT-Image-Mar-10-2026-10_54_44-AM.png" alt="ما بعد البرنامج">
      </div>
    </div>
  </div>
</section>

<!-- ════════ جديد: الرسوم والعروض ════════ -->
<section class="pricing" id="pricing">
  <div class="wrap center">
    <span class="eyebrow">الرسوم والعروض</span>
    <h2 class="sec-title">استثمارٌ في عُمر ابنك — لا نفقةٌ تمضي</h2>
    <div class="price-deadline"><span class="live" style="width:9px;height:9px;border-radius:50%;background:#ff6b6b;display:inline-block"></span> عروض التسجيل المبكر تنتهي ٣٠ يونيو — أو بنفاد المقاعد</div>

    <div class="price-grid" style="text-align:start">
      <div class="price-card feat-card reveal">
        <span class="ribbon">الأكثر طلباً</span>
        <div class="plan">التسجيل المبكر</div>
        <div class="amount">٤٨٩ <small>ر.ع</small></div>
        <div class="full">٧٠٠ ر.ع</div>
        <div class="save">توفير ٢١١ ريالاً</div>
        <div class="cond">للطالب الواحد — قبل ٣٠ يونيو</div>
      </div>
      <div class="price-card reveal">
        <div class="plan">عرض الإخوة</div>
        <div class="amount">٤٦٩ <small>ر.ع</small></div>
        <div class="full">٧٠٠ ر.ع</div>
        <div class="save">لكلّ أخٍ من العائلة</div>
        <div class="cond">عند تسجيل أخوين من نفس العائلة</div>
      </div>
      <div class="price-card reveal">
        <div class="plan">التسجيل الجماعي</div>
        <div class="amount">٤٥٩ <small>ر.ع</small></div>
        <div class="full">٧٠٠ ر.ع</div>
        <div class="save">توفير ٢٤١ ريالاً</div>
        <div class="cond">للطالب — عند تسجيل ٣ فأكثر</div>
      </div>
      <div class="price-card reveal">
        <div class="plan">السعر الكامل</div>
        <div class="amount">٧٠٠ <small>ر.ع</small></div>
        <div class="full" style="visibility:hidden">—</div>
        <div class="save">شهرٌ كامل · إقامةٌ وإعاشة</div>
        <div class="cond">يشمل ثلاث وجباتٍ يومياً والإشراف التام</div>
      </div>
    </div>
    <div style="margin-top:30px;color:rgba(255,255,255,.75);font-size:.96rem">
      الدفع عبر منصة «ثواني» الآمنة — دفعةً واحدة أو على ثلاثة أقساط · لا يُطلب أيّ دفعٍ قبل القبول والتوقيع.
    </div>
    <div style="margin-top:26px"><a href="#register" class="btn btn-gold">احجز بالسعر المبكر</a></div>
  </div>
</section>

<!-- ════════ جديد: سوّق واربح ════════ -->
<section class="affiliate" id="affiliate">
  <div class="wrap center">
    <span class="eyebrow">فرصةٌ لك ولأبنائك</span>
    <h2 class="sec-title">سوّق واربح — وادْعُ مَن تُحبّ</h2>
    <p class="sec-sub">اجمع أبناءَ أصدقائك مع ابنك في الرحلة نفسها — ولك على كلّ مُسجَّلٍ عمولةٌ مجزية.</p>

    <div class="aff-grid" style="text-align:start">
      <div class="aff-card reveal">
        <h3>للمسوّقين</h3>
        <div class="big">٣٠ ر.ع<span style="font-size:1rem;color:rgba(255,255,255,.8)"> فأكثر / لكل مُسجَّل</span></div>
        <p>عمولةٌ مباشرة عن كلّ قائدٍ يلتحق عبرك — برابطٍ أو رمزٍ خاصٍّ بك.</p>
        <ul>
          <li>رمزٌ خاصٌّ يُتابع تسجيلاتك</li>
          <li>صرفٌ بعد تأكيد القبول والسداد</li>
          <li>مفتوحٌ للجميع — ومحفّظي القرآن والأئمة والأندية</li>
        </ul>
      </div>
      <div class="aff-card reveal">
        <h3>لأولياء الأمور</h3>
        <div class="big">مزايا مضاعفة</div>
        <p>ابنك مُسجَّل؟ ادْعُ أقاربك وأصدقاءك — فيكون أبناؤهم مع ابنك:</p>
        <ul>
          <li>المُسجَّل الجديد ينال المزايا نفسها التي نلتَها</li>
          <li>وأنت تنال عمولة المسوّق عن كلّ مَن سجّل عبرك</li>
          <li>مجموعةٌ خاصة للآباء وأخرى للأمهات للتنسيق</li>
        </ul>
      </div>
    </div>

    <div class="aff-cta">
      <a href="{{ $affiliateUrl }}" class="btn btn-gold">احصل على رمزك عبر الواتساب</a>
    </div>
    <!-- المبرّم: ربط الرمز/التتبع بالـ Backend (UTM أو referral code) لاحتساب العمولة آلياً -->
  </div>
</section>

<!-- ════════ الاستمارة ════════ -->
<section class="register" id="register">
  <div class="wrap center">
    <span class="eyebrow">الانضمام</span>
    <h2 class="sec-title">احجز مقعد ابنك</h2>
    <p class="sec-sub">المقاعد محدودة وتُمنح بالأولوية — سجّل الآن وسيتواصل معك فريقنا بكل التفاصيل.</p>
  </div>
  <div class="wrap">
    <div class="reg-box">
      <div class="reg-info">
        <h3>لماذا تُبادر الآن؟</h3>
        <p>الدفعة الأولى ٥٠ مقعداً فقط — حين تُغلق، لا تُفتح حتى الموسم القادم.</p>
        <div class="point"><i>◆</i><span>السعر المبكر ٤٨٩ ر.ع ينتهي ٣٠ يونيو</span></div>
        <div class="point"><i>◆</i><span>قبولٌ بالأولوية للمُبادرين</span></div>
        <div class="point"><i>◆</i><span>ثلاثة أعلامٍ يقودون: الغافري · صوان · أبو بلج</span></div>
        <div class="point"><i>◆</i><span>إقامةٌ آمنة داخل عُمان — بلا سفرٍ ولا تأشيرات</span></div>
      </div>
      <div class="reg-form" style="display:flex;flex-direction:column;justify-content:center;align-items:flex-start;gap:18px">
        <h3>تابع الحجز عبر صفحة الفعالية</h3>
        <p class="micro" style="text-align:start;margin-top:0">استكمل الطلب من خلال النظام، ثم يتابع الفريق معك خطوات القبول والتوقيع والسداد.</p>
        <a href="{{ $registrationUrl }}" class="btn btn-gold">اذهب إلى صفحة الحجز</a>
        <a href="{{ $eventUrl }}" style="color:var(--teal);font-family:var(--display);font-weight:600">عرض تفاصيل الفعالية</a>
      </div>
    </div>
  </div>
</section>

<!-- ════════ الأسئلة الشائعة (مختصرة — تبقى بقيتها كما في الصفحة الحالية) ════════ -->
<section class="faq" id="faq">
  <div class="wrap center">
    <span class="eyebrow">الأسئلة الشائعة</span>
    <h2 class="sec-title">ما يدور في ذهن كلّ ولي أمر</h2>
  </div>
  <div class="faq-list">
    <div class="faq-cat">التعريف والقبول</div>
    <details open><summary>متى يُقام البرنامج وأين؟</summary><div class="ans">في مخيم بيرحاء بولاية إبراء، سلطنة عُمان — من <b>١ إلى ٣٠ يوليو ٢٠٢٦</b>، داخل الأراضي العُمانية بلا سفرٍ ولا تأشيرات.</div></details>
    <details><summary>لمن يُوجَّه البرنامج؟</summary><div class="ans">لطلاب الصفوف السابع والثامن والتاسع (نحو ١٣–١٥ سنة) من الفتيان العُمانيين في نسخته الأولى.</div></details>
    <details><summary>كم عدد المقاعد؟ ولماذا محدودة؟</summary><div class="ans">خمسون مقعداً فقط — التزاماً بجودة التجربة ونسبة إشرافٍ عالية لكل قائد، لا قراراً تسويقياً.</div></details>
    <details><summary>كيف أُسجِّل وما خطوات القبول؟</summary><div class="ans">تعبئة الاستمارة في الموقع، ثم فرز الطلبات بالأولوية، فلقاءٌ تعريفي، فالقبول والعقد، ثم السداد — ليُصبح الطالب قائداً رسمياً.</div></details>

    <div class="faq-cat">الرسوم والأمان</div>
    <details><summary>كم تكلفة البرنامج؟</summary><div class="ans">٧٠٠ ر.ع كاملاً، وتتوفّر عروض مبكرة: <b>٤٨٩</b> للفرد، <b>٤٦٩</b> للإخوة، <b>٤٥٩</b> للمجموعة — تنتهي ٣٠ يونيو.</div></details>
    <details><summary>هل البرنامج آمنٌ لابني؟</summary><div class="ans">نعم — داخل عُمان، بإشرافٍ تامٍّ على مدار الساعة، وجدولٍ لا يترك لحظةً دون رعاية، وولي الأمر على اطّلاعٍ بكل شيء.</div></details>
    <details><summary>هل أتواصل مع ابني خلال البرنامج؟</summary><div class="ans">نعم، في الوقت المفتوح اليومي (٢:٠٠–٣:٣٠) وجزءٍ من السبت. للتواصل مع الفريق: واتساب 96874155123.</div></details>
    <p style="text-align:center;margin-top:22px;color:var(--muted)">للأسئلة كاملةً، تواصل معنا عبر <a href="https://wa.me/96874155123" style="color:var(--teal);font-weight:600">الواتساب</a>.</p>
  </div>
</section>

<!-- ════════ التذييل ════════ -->
<footer class="foot">
  <div class="wrap">
    <img src="https://byruhaa.com/wp-content/uploads/2023/02/باللون-الأبيض-شعار-بيرحاء-الجديد-0٤-scaled.png" alt="بيرحاء">
    <h3>برنامج «مهاجر إلى ربي» ٢٠٢٦</h3>
    <p>بقيادة أبي بلج عبدالله العيسري · مؤسس مجموعة العيسري التعليمية</p>
    <p>مخيم بيرحاء · إبراء · سلطنة عُمان</p>
    <div style="margin-top:18px"><a href="#register" class="btn btn-teal">احجز مقعدك</a></div>
    <div class="copy">بيرحاء ٢٠٢٦ © جميع الحقوق محفوظة</div>
  </div>
</footer>

<a class="wa" href="https://wa.me/96874155123" aria-label="تواصل عبر الواتساب">
  <span>تواصل معنا</span> 💬
</a>

<script>
(function(){
  // تحويل الأرقام إلى هندية-عربية
  const map={'0':'٠','1':'١','2':'٢','3':'٣','4':'٤','5':'٥','6':'٦','7':'٧','8':'٨','9':'٩'};
  const ar=n=>String(n).replace(/[0-9]/g,d=>map[d]);

  /* ════════ المبرّم: اضبط هذين السطرين ════════ */
  const target = new Date('2026-07-01T00:00:00+04:00').getTime(); // انطلاق الدفعة (توقيت عُمان)
  let seatsLeft = 37;                                              // المقاعد المتبقّية (قابلة للتعديل)
  /* ═══════════════════════════════════════════ */

  const seatsEl=document.getElementById('seatsLeft');
  if(seatsEl) seatsEl.textContent=ar(seatsLeft);

  const cd=document.getElementById('countdown');
  const mini=document.getElementById('miniCount');
  const dEl=cd&&cd.querySelector('[data-d]'), hEl=cd&&cd.querySelector('[data-h]'),
        mEl=cd&&cd.querySelector('[data-m]'), sEl=cd&&cd.querySelector('[data-s]');

  function tick(){
    const diff=target-Date.now();
    if(diff<=0){
      if(cd) cd.innerHTML='<div style="color:#fff;font-family:Reem Kufi">انطلقت الدفعة الأولى — بإذن الله 🌿</div>';
      if(mini) mini.textContent='انطلق البرنامج';
      return;
    }
    const d=Math.floor(diff/864e5), h=Math.floor(diff%864e5/36e5),
          m=Math.floor(diff%36e5/6e4), s=Math.floor(diff%6e4/1e3);
    if(dEl){dEl.textContent=ar(d);hEl.textContent=ar(h);mEl.textContent=ar(m);sEl.textContent=ar(s);}
    if(mini) mini.textContent=ar(d)+' يوم · '+ar(h)+' س · '+ar(m)+' د';
  }
  tick(); setInterval(tick,1000);

  // كشفٌ عند التمرير
  const io=new IntersectionObserver((ents)=>{
    ents.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);}});
  },{threshold:.12});
  document.querySelectorAll('.reveal').forEach(el=>io.observe(el));
})();
</script>
</body>
</html>
