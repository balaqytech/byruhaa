<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>قهوة بِيرُحاء — قريبًا</title>
<meta name="description" content="قهوة بِيرُحاء في إبراء: ليس مقهى، بل عالم لفتياننا من الصف السابع إلى الثاني عشر. سجّل لتعرف موعد الافتتاح أولًا.">
<meta property="og:title" content="قهوة بِيرُحاء… تُفتح قريبًا">
<meta property="og:description" content="عبادة، علم، عمل، لعب، نموّ — في مكان واحد. سجّل لتكون أول من يعلم.">
<meta property="og:image" content="{{ asset('images/coffee-byruha-hero.webp') }}">
<meta name="theme-color" content="#0f3d33">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Noto+Kufi+Arabic:wght@600;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f4faf7; --surface:#ffffff; --ink:#10251f; --muted:#5b6f68;
  --brand:#0f3d33; --brand-2:#1f6b58; --mint:#dff6ef; --gold:#b8893a;
  --line:#d6e6df; --danger:#b3261e; --radius:18px;
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    --bg:#0b1714; --surface:#11211d; --ink:#e8f3ef; --muted:#9db3ab;
    --brand:#7fd1b9; --brand-2:#5bb89d; --mint:#16302a; --gold:#d8ae62; --line:#23403a; --danger:#ff8a80;
  }
}
:root[data-theme="dark"]{
  --bg:#0b1714; --surface:#11211d; --ink:#e8f3ef; --muted:#9db3ab;
  --brand:#7fd1b9; --brand-2:#5bb89d; --mint:#16302a; --gold:#d8ae62; --line:#23403a; --danger:#ff8a80;
}
*{box-sizing:border-box}
html,body{margin:0}
body{background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",system-ui,sans-serif;line-height:1.7;-webkit-font-smoothing:antialiased}
.wrap{max-width:480px;margin:0 auto;padding:0 16px}
header{display:flex;justify-content:center;padding:18px 0 10px}
header img{height:40px}
.logo-dark{display:none}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) .logo-light{display:none}:root:not([data-theme="light"]) .logo-dark{display:block}}

/* الفيديو بمقاس الريل 9:16 */
.reel{position:relative;width:100%;max-width:400px;margin:0 auto;aspect-ratio:9/16;border-radius:24px;overflow:hidden;background:#0a1a16 url('{{ asset('images/coffee-byruha-hero.webp') }}') center/cover no-repeat;box-shadow:0 20px 50px -20px rgba(15,61,51,.55)}
.reel video{width:100%;height:100%;object-fit:cover;display:block}
.reel .badge{position:absolute;top:14px;right:14px;background:rgba(0,0,0,.45);backdrop-filter:blur(6px);color:#fff;font-size:13px;padding:5px 12px;border-radius:999px;letter-spacing:.3px}
.reel .badge i{display:inline-block;width:7px;height:7px;border-radius:50%;background:#ff5a4f;margin-inline-end:6px;vertical-align:middle;animation:pulse 1.6s infinite}
@keyframes pulse{50%{opacity:.25}}
.reel .sound{position:absolute;bottom:14px;left:14px;border:0;background:rgba(0,0,0,.5);color:#fff;font:inherit;font-size:13px;padding:7px 14px;border-radius:999px;cursor:pointer}
.reel .cta-float{position:absolute;bottom:14px;right:14px;background:var(--gold);color:#1b1205;text-decoration:none;font-weight:600;font-size:14px;padding:7px 14px;border-radius:999px}

.hero-copy{text-align:center;padding:26px 0 8px}
.eyebrow{color:var(--gold);font-weight:600;font-size:14px;margin:0 0 4px}
h1{font-family:"Noto Kufi Arabic",sans-serif;font-weight:800;font-size:30px;line-height:1.35;margin:0 0 10px;color:var(--brand)}
.lead{color:var(--muted);margin:0 auto;max-width:36ch;font-size:16px}
.pillars{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:18px 0 6px;padding:0;list-style:none}
.pillars li{background:var(--mint);color:var(--brand);font-weight:600;font-size:14px;padding:6px 14px;border-radius:999px}

/* الاستمارة */
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:22px 18px;margin:22px 0}
.card h2{font-family:"Noto Kufi Arabic",sans-serif;font-size:21px;margin:0 0 2px;color:var(--brand)}
.card .sub{color:var(--muted);font-size:14px;margin:0 0 18px}
.field{margin-bottom:16px}
label,.label{display:block;font-weight:600;font-size:15px;margin-bottom:7px}
input[type=text],input[type=tel],select{width:100%;font:inherit;font-size:16px;color:var(--ink);background:var(--bg);border:1.5px solid var(--line);border-radius:12px;padding:12px 14px;outline:none;transition:border-color .15s}
input:focus,select:focus{border-color:var(--brand-2)}
.phone{display:flex;gap:8px;direction:ltr}
.phone span{display:flex;align-items:center;padding:0 12px;border:1.5px solid var(--line);border-radius:12px;background:var(--mint);color:var(--brand);font-weight:600}
.phone input{direction:ltr;text-align:left;letter-spacing:1px}
.chips{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}
.chips.four{grid-template-columns:repeat(4,1fr)}
.chip input{position:absolute;opacity:0;pointer-events:none}
.chip span{display:block;text-align:center;border:1.5px solid var(--line);border-radius:12px;padding:10px 6px;font-weight:500;font-size:15px;cursor:pointer;background:var(--bg);transition:all .15s}
.chip input:checked+span{border-color:var(--brand-2);background:var(--mint);color:var(--brand);font-weight:600}
.chip input:focus-visible+span{outline:2px solid var(--brand-2);outline-offset:2px}
.hidden{display:none}
.err{color:var(--danger);font-size:13px;margin-top:5px;display:none}
.field.invalid .err{display:block}
.field.invalid input,.field.invalid select{border-color:var(--danger)}
button.submit{width:100%;border:0;border-radius:14px;background:var(--brand);color:#fff;font:inherit;font-weight:600;font-size:17px;padding:15px;cursor:pointer;margin-top:4px}
:root[data-theme="dark"] button.submit{color:#0b1714}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) button.submit{color:#0b1714}}
button.submit[disabled]{opacity:.6;cursor:wait}
.privacy{font-size:12.5px;color:var(--muted);text-align:center;margin:12px 0 0}
.privacy a{color:inherit}
.alt{display:flex;align-items:center;gap:12px;margin-top:18px;padding-top:16px;border-top:1px dashed var(--line)}
.alt p{margin:0;font-size:14px;color:var(--muted);flex:1}
.alt a{white-space:nowrap;text-decoration:none;font-weight:600;font-size:14px;color:#fff;background:#1f8f5a;padding:9px 14px;border-radius:12px}

/* شاشة النجاح */
.done{text-align:center;padding:10px 0}
.done .tick{width:62px;height:62px;border-radius:50%;background:var(--mint);color:var(--brand);display:grid;place-items:center;margin:0 auto 12px;font-size:30px}
.done h2{margin-bottom:6px}
.done p{color:var(--muted);margin:0 0 18px}
.share{display:inline-block;text-decoration:none;font-weight:600;color:var(--brand);border:1.5px solid var(--brand-2);padding:11px 18px;border-radius:12px}
footer{text-align:center;color:var(--muted);font-size:13px;padding:10px 0 36px}
footer a{color:inherit}
</style>
</head>
<body>
<div class="wrap">

  <header>
    <a href="{{ route('home') }}" aria-label="بِيرُحاء إبراء">
      <img class="logo-light" src="{{ asset('logo.png') }}" alt="بِيرُحاء إبراء">
      <img class="logo-dark" src="{{ asset('logo-dark.png') }}" alt="بِيرُحاء إبراء">
    </a>
  </header>

  <!-- الفيديو (ريل 9:16) -->
  <section class="reel">
    <video id="teaser" autoplay muted loop playsinline preload="metadata"
           poster="{{ asset('images/coffee-byruha-hero.webp') }}">
      <source src="{{ asset('coffee-teaser.mp4') }}" type="video/mp4">
    </video>
    <div class="badge"><i></i>قريبًا في إبراء</div>
    <button class="sound hidden" id="soundBtn" type="button">🔇 شغّل الصوت</button>
    <a class="cta-float" href="#join">سجّل الآن ↓</a>
  </section>

  <section class="hero-copy">
    <p class="eyebrow">قهوة بِيرُحاء · مخيم بِيرُحاء</p>
    <h1>ليس مقهى… بل عالَم.<br>والباب يُفتح قريبًا.</h1>
    <p class="lead">لفتياننا من الصف السابع إلى الثاني عشر: فنجانٌ يبدأ منه يومٌ كامل في بيئةٍ آمنة يطمئن إليها وليّ الأمر.</p>
    <ul class="pillars">
      <li>عبادة</li><li>علم</li><li>عمل</li><li>لعب</li><li>نموّ</li>
    </ul>
  </section>

  <!-- الاستمارة -->
  <section class="card" id="join">
    <div id="formView">
      <h2>كن أول من يعلم</h2>
      <p class="sub">نصف دقيقة، وتصلك رسالة موعد الافتتاح قبل الإعلان العام.</p>

      <form id="waitlist" action="{{ route('coffee.waitlist.store') }}" method="post" novalidate>
        @csrf
        <div class="field" data-name="role">
          <span class="label">أنا</span>
          <div class="chips">
            <label class="chip"><input type="radio" name="role" value="parent" checked><span>وليّ أمر</span></label>
            <label class="chip"><input type="radio" name="role" value="teacher"><span>معلّم</span></label>
            <label class="chip"><input type="radio" name="role" value="principal"><span>إدارة مدرسة</span></label>
            <label class="chip"><input type="radio" name="role" value="directorate"><span>مديرية التعليم</span></label>
          </div>
        </div>

        <div class="field" data-name="name">
          <label for="name">الاسم</label>
          <input id="name" name="name" type="text" autocomplete="name" placeholder="الاسم الثلاثي">
          <div class="err">اكتب اسمك من فضلك</div>
        </div>

        <div class="field" data-name="phone">
          <label for="phone">رقم الهاتف (واتساب)</label>
          <div class="phone"><span>+968</span><input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="8" placeholder="9XXXXXXX"></div>
          <div class="err">رقم عُماني من 8 أرقام يبدأ بـ 7 أو 9</div>
        </div>

        <div class="field" data-name="wilayat">
          <label for="wilayat">الولاية</label>
          <input id="wilayat" name="wilayat" type="text" list="wilayat-list" placeholder="مثال: إبراء">
          <datalist id="wilayat-list">
            <option value="إبراء"><option value="المضيبي"><option value="بدية"><option value="القابل">
            <option value="وادي بني خالد"><option value="دماء والطائيين"><option value="صور">
            <option value="جعلان بني بو علي"><option value="جعلان بني بو حسن"><option value="الكامل والوافي">
            <option value="سمائل"><option value="إزكي"><option value="نزوى"><option value="مسقط"><option value="السيب"><option value="بوشر">
          </datalist>
          <div class="err">اختر ولايتك</div>
        </div>

        <!-- لوليّ الأمر -->
        <div class="field" data-name="sons_count" id="sonsField">
          <span class="label">عدد الأبناء الذكور من الصف ٧ إلى ١٢</span>
          <div class="chips four">
            <label class="chip"><input type="radio" name="sons_count" value="1"><span>١</span></label>
            <label class="chip"><input type="radio" name="sons_count" value="2"><span>٢</span></label>
            <label class="chip"><input type="radio" name="sons_count" value="3"><span>٣</span></label>
            <label class="chip"><input type="radio" name="sons_count" value="4+"><span>٤+</span></label>
          </div>
          <div class="err">اختر العدد</div>
        </div>

        <!-- للمدرسة والمديرية -->
        <div class="field hidden" data-name="school" id="schoolField">
          <label for="school" id="schoolLabel">اسم المدرسة</label>
          <input id="school" name="school" type="text" placeholder="مثال: مدرسة إبراء للتعليم الأساسي">
          <div class="err">هذا الحقل مطلوب</div>
        </div>

        <button class="submit" id="submitBtn" type="submit">أبلغوني بموعد الافتتاح</button>
        <p class="privacy">نستخدم بياناتك لإبلاغك بالافتتاح وما يخص بِيرُحاء فقط. <a href="{{ route('policies.show', 'privacy') }}" target="_blank" rel="noopener">سياسة الخصوصية</a></p>
      </form>

      <div class="alt">
        <p>تفضّل المحادثة؟ مساعد بِيرُحاء الذكي يجيبك ويسجّلك.</p>
        <a id="waBtn" href="#" target="_blank" rel="noopener">واتساب</a>
      </div>
    </div>

    <div id="doneView" class="done hidden" aria-live="polite">
      <div class="tick">✓</div>
      <h2>أنت في القائمة</h2>
      <p id="doneMsg">ستصلك رسالة موعد الافتتاح على واتساب قبل الإعلان العام.</p>
      <a class="share" id="shareBtn" href="#" target="_blank" rel="noopener">شارك الخبر مع وليّ أمرٍ آخر</a>
    </div>
  </section>

  <footer>
    مخيم بِيرُحاء · ولاية إبراء · <a href="{{ route('home') }}">byruhaa.com</a><br>
    © 2026 بِيرُحاء إبراء
  </footer>
</div>

<script>
const CONFIG = {
  endpoint: @json(route('coffee.waitlist.store')),
  whatsapp: '96874155123',
  pageUrl: @json(route('home'))
};

// الصوت
const video = document.getElementById('teaser'), soundBtn = document.getElementById('soundBtn');
video.addEventListener('loadeddata', () => soundBtn.classList.remove('hidden'));
soundBtn.addEventListener('click', () => {
  video.muted = !video.muted;
  if (!video.muted) { video.currentTime = 0; video.play(); }
  soundBtn.textContent = video.muted ? '🔇 شغّل الصوت' : '🔊 كتم الصوت';
});

// رابط واتساب للمساعد الذكي
document.getElementById('waBtn').href =
  'https://wa.me/' + CONFIG.whatsapp + '?text=' +
  encodeURIComponent('السلام عليكم، أرغب بالتسجيل في قائمة افتتاح قهوة بِيرُحاء.');

// تبديل الحقل الخامس حسب الصفة
const form = document.getElementById('waitlist');
const sonsField = document.getElementById('sonsField'), schoolField = document.getElementById('schoolField');
const schoolLabel = document.getElementById('schoolLabel'), schoolInput = document.getElementById('school');
form.addEventListener('change', e => {
  if (e.target.name !== 'role') return;
  const r = e.target.value, isParent = r === 'parent';
  sonsField.classList.toggle('hidden', !isParent);
  schoolField.classList.toggle('hidden', isParent);
  if (r === 'directorate') { schoolLabel.textContent = 'المديرية والقسم'; schoolInput.placeholder = 'مثال: تعليمية شمال الشرقية — الإشراف'; }
  else { schoolLabel.textContent = 'اسم المدرسة'; schoolInput.placeholder = 'مثال: مدرسة إبراء للتعليم الأساسي'; }
});

// أرقام عربية ← لاتينية في الهاتف
const phone = document.getElementById('phone');
phone.addEventListener('input', () => {
  phone.value = phone.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 8);
});

function setInvalid(name, bad){ form.querySelector(`[data-name="${name}"]`).classList.toggle('invalid', bad); return bad; }

form.addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(form), role = fd.get('role');
  let bad = false;
  bad = setInvalid('name', (fd.get('name')||'').trim().length < 3) || bad;
  bad = setInvalid('phone', !/^[79]\d{7}$/.test(fd.get('phone')||'')) || bad;
  bad = setInvalid('wilayat', !(fd.get('wilayat')||'').trim()) || bad;
  if (role === 'parent') { bad = setInvalid('sons_count', !fd.get('sons_count')) || bad; setInvalid('school', false); }
  else { bad = setInvalid('school', !(fd.get('school')||'').trim()) || bad; setInvalid('sons_count', false); }
  if (bad) { form.querySelector('.invalid input')?.focus(); return; }

  const qs = new URLSearchParams(location.search);
  const payload = {
    role, name: fd.get('name').trim(), phone: '+968' + fd.get('phone'),
    wilayat: fd.get('wilayat').trim(),
    sons_count: role === 'parent' ? fd.get('sons_count') : null,
    school: role !== 'parent' ? fd.get('school').trim() : null,
    source: 'website', utm_source: qs.get('utm_source'), utm_medium: qs.get('utm_medium'), utm_campaign: qs.get('utm_campaign')
  };

  const btn = document.getElementById('submitBtn');
  btn.disabled = true; btn.textContent = 'جارٍ التسجيل…';
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(CONFIG.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify(payload)
    });
    if (!res.ok) throw new Error(res.status);
    showDone(payload);
  } catch (err) {
    btn.disabled = false; btn.textContent = 'أبلغوني بموعد الافتتاح';
    alert('تعذّر التسجيل الآن. جرّب مرة أخرى أو سجّل عبر واتساب.');
  }
});

function showDone(p){
  document.getElementById('formView').classList.add('hidden');
  document.getElementById('doneView').classList.remove('hidden');
  const first = p.name.split(' ')[0];
  document.getElementById('doneMsg').textContent =
    p.role === 'parent'
      ? `شكرًا ${first}. ستصلك رسالة موعد الافتتاح على واتساب قبل الإعلان العام.`
      : `شكرًا ${first}. سنبلغك بموعد الافتتاح، ونتواصل معك بشأن زيارات الطلبة.`;
  const msg = p.role === 'parent'
    ? 'قهوة بِيرُحاء في إبراء تُفتح قريبًا لأبنائنا من الصف ٧ إلى ١٢ — سجّل لتعرف الموعد أولًا: '
    : 'قهوة بِيرُحاء في إبراء تُفتح قريبًا لطلبة الصفوف ٧–١٢ — سجّل لتعرف الموعد أولًا: ';
  document.getElementById('shareBtn').href = 'https://wa.me/?text=' + encodeURIComponent(msg + CONFIG.pageUrl + '?utm_source=share');
  document.getElementById('join').scrollIntoView({behavior:'smooth', block:'start'});
}
</script>
</body>
</html>
