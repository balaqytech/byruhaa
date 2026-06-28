@php
    $eventUrl = $event instanceof App\Models\Event ? route('events.show', $event) : route('events.index');
    $registrationUrl =
        $event instanceof App\Models\Event ? route('customer.events.show', $event) : route('events.index');
    $affiliateUrl = route('affiliate.register');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برنامج «مهاجر إلى ربي» — بيرحاء · إبراء · صيف ٢٠٢٦</title>
    <meta name="description"
        content="٣٠ يوماً منظّمة بالدقيقة لابنك في قلب الطبيعة — قرآن كريم، نحوٌ بالفطرة، ومهارات الحياة، بقيادة أبي بلج وثلاثة أعلام. مقاعد محدودة لطلاب الصفوف ٧–٩ | إبراء، عُمان">
    <script>
        document.documentElement.className = 'js';
    </script>

    <!--
  ================================================================
  صفحة «مهاجر إلى ربي» — نسخة محدّثة جاهزة للمبرّم
  المعدّل/الجديد موسومٌ بكلمتَي «جديد» و«معدّل» داخل التعليقات.
  ألوان الهوية: كحلي 16263F · فيروزي 0E7C7B · ذهبي B7892B
  ================================================================
-->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap">
    </noscript>

    @verbatim
        <style>
            :root {
                --bp-bg: #0F1B2E;
                --bp-panel: #162844;
                --bp-panel-soft: rgba(15, 27, 46, 0.82);
                --bp-panel-raised: rgba(255, 255, 255, .05);
                --bp-text: #ffffff;
                --bp-text-soft: #ddd8cf;
                --bp-text-muted: #9ca3af;
                --bp-border: rgba(255, 255, 255, .08);
                --bp-border-strong: rgba(255, 255, 255, .18);
                --bp-primary: #F9A474;
                --bp-primary-2: #FCD2B7;
                --bp-secondary: #c3a3ff;
                --bp-success: #59d9a6;
                --bp-warning: #f4bf63;
                --bp-danger: #ff6b6b;
                --bp-teal: #47d6cc;
                --bp-radius-md: 18px;
                --bp-radius-lg: 24px;
                --bp-radius-xl: 32px;
                --bp-radius-pill: 9999px;
                --bp-shadow-xs: 0 2px 10px rgba(0, 0, 0, .22);
                --bp-shadow-sm: 0 10px 24px rgba(4, 8, 15, .30);
                --bp-shadow-md: 0 18px 40px rgba(2, 6, 14, .42);
                --bp-glow: 0 0 0 1px rgba(249, 164, 116, .16), 0 0 30px rgba(249, 164, 116, .10);
                --bp-glow-strong: 0 0 0 1px rgba(249, 164, 116, .24), 0 0 50px rgba(249, 164, 116, .16), 0 0 100px rgba(249, 164, 116, .06);
                --ease-out: cubic-bezier(.23, 1, .32, 1);
                --ease-in-out: cubic-bezier(.77, 0, .175, 1);
                --display: "Aref Ruqaa", "IBM Plex Sans Arabic", "Geeza Pro", "Segoe UI", Tahoma, system-ui, sans-serif;
                --body: "Noto Naskh Arabic", "IBM Plex Sans Arabic", "Geeza Pro", "Segoe UI", Tahoma, system-ui, serif;
                --amiri: "Amiri", "Noto Naskh Arabic", "IBM Plex Sans Arabic", serif;
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0
            }

            html {
                scroll-behavior: smooth;
                background: var(--bp-bg)
            }

            body {
                min-height: 100svh;
                font-family: var(--body);
                color: var(--bp-text);
                background:
                    linear-gradient(rgba(255, 255, 255, .014) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, .014) 1px, transparent 1px),
                    var(--bp-bg);
                background-size: 72px 72px, 72px 72px, auto;
                line-height: 1.82;
                -webkit-font-smoothing: antialiased;
                overflow-x: hidden;
            }

            h1,
            h2,
            h3,
            h4,
            .disp {
                font-family: var(--display);
                line-height: 1.28;
                font-weight: 700;
                color: var(--bp-text);
                letter-spacing: 0;
                text-wrap: balance
            }

            a {
                color: inherit;
                text-decoration: none
            }

            img {
                display: block;
                max-width: 100%
            }

            .wrap {
                width: min(1240px, calc(100% - 48px));
                margin-inline: auto;
                padding-inline: 0
            }

            section {
                position: relative;
                padding: 120px 0;
                background: var(--bp-bg)
            }

            section::before {
                content: "";
                position: absolute;
                inset-inline: 0;
                top: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent 5%, rgba(249, 164, 116, .08) 20%, rgba(255, 255, 255, .14) 50%, rgba(249, 164, 116, .08) 80%, transparent 95%);
                pointer-events: none
            }

            .center {
                text-align: center
            }

            .center .sec-sub {
                margin-inline: auto
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 32px;
                padding: 6px 16px;
                margin-bottom: 20px;
                border: 1px solid rgba(249, 164, 116, .18);
                border-radius: var(--bp-radius-pill);
                background: linear-gradient(135deg, rgba(249, 164, 116, .06), rgba(249, 164, 116, .02));
                box-shadow: 0 0 24px rgba(249, 164, 116, .06), inset 0 1px 0 rgba(255, 255, 255, .04);
                color: var(--bp-primary);
                font-family: var(--display);
                font-weight: 700;
                font-size: .82rem;
                line-height: 1.4;
                letter-spacing: 0;
                backdrop-filter: blur(16px);
            }

            .sec-title {
                font-size: clamp(2.4rem, 5vw, 4rem);
                margin-bottom: 16px;
                background: linear-gradient(180deg, #fff 10%, #e8e0d4 70%, #c4b9aa 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
                text-shadow: 0 18px 60px rgba(255, 255, 255, .06)
            }

            .sec-sub {
                max-width: 760px;
                color: var(--bp-text-muted);
                font-size: 1.08rem;
                line-height: 1.95
            }

            .btn {
                position: relative;
                isolation: isolate;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 50px;
                padding: 13px 26px;
                border-radius: var(--bp-radius-pill);
                border: 1px solid transparent;
                font-family: var(--display);
                font-weight: 700;
                font-size: 1rem;
                line-height: 1.2;
                cursor: pointer;
                transition: transform 200ms var(--ease-out), background-color 200ms ease, border-color 200ms ease, color 200ms ease, box-shadow 280ms var(--ease-out);
            }

            .btn:focus-visible {
                outline: 0;
                box-shadow: 0 0 0 4px rgba(249, 164, 116, .34), var(--bp-glow)
            }

            .btn:active {
                transform: scale(.96)
            }

            .btn-gold {
                background: linear-gradient(135deg, var(--bp-primary) 0%, #F7B38E 55%, var(--bp-primary-2) 100%);
                color: #1A120D;
                border-color: transparent;
                box-shadow: var(--bp-shadow-xs), inset rgba(255, 255, 255, .42) 0 6px 0 -5px, rgba(249, 164, 116, .45) 0 4px 10px -5px;
            }

            .btn-gold::before {
                content: "";
                position: absolute;
                inset: -1px;
                border-radius: inherit;
                background: linear-gradient(135deg, rgba(255,255,255,.3), rgba(255,255,255,0) 60%);
                opacity: 0;
                transition: opacity 300ms ease;
                pointer-events: none;
                z-index: -1;
            }

            .btn-teal {
                background: linear-gradient(135deg, var(--bp-success), var(--bp-teal));
                color: #061313;
                box-shadow: var(--bp-shadow-xs), inset rgba(255, 255, 255, .38) 0 6px 0 -5px, rgba(89, 217, 166, .35) 0 4px 10px -5px
            }

            .btn-ghost {
                background: rgba(255, 255, 255, .04);
                border-color: var(--bp-border);
                color: var(--bp-text);
                backdrop-filter: blur(16px);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04)
            }

            .topbar {
                position: sticky;
                top: 0;
                z-index: 80;
                background: rgba(8, 12, 22, .88);
                border-bottom: 1px solid rgba(249, 164, 116, .08);
                backdrop-filter: blur(20px) saturate(1.2);
                color: var(--bp-text)
            }

            .topbar .wrap {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 14px;
                padding: 10px 0;
                font-family: var(--display);
                font-size: .92rem;
                line-height: 1.5;
                flex-wrap: wrap
            }

            .topbar .dot,
            .seats-pill .live {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--bp-primary);
                box-shadow: 0 0 0 5px rgba(249, 164, 116, .12)
            }

            .topbar .mini-count {
                color: var(--bp-primary);
                font-weight: 800
            }

            .topbar a.mini-cta {
                margin-inline-start: auto;
                min-height: 34px;
                display: inline-flex;
                align-items: center;
                padding: 6px 14px;
                border: 1px solid rgba(249, 164, 116, .28);
                border-radius: var(--bp-radius-pill);
                background: rgba(249, 164, 116, .08);
                font-weight: 800;
                transition: transform 160ms var(--ease-out), border-color 180ms ease, background-color 180ms ease
            }

            .topbar a.mini-cta:active {
                transform: scale(.97)
            }

            .nav {
                position: absolute;
                top: 50px;
                left: 0;
                right: 0;
                z-index: 50
            }

            .nav .wrap {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                padding: 20px 0
            }

            .nav .logo {
                height: 42px;
                filter: brightness(0) invert(1);
                opacity: .9
            }

            .nav ul {
                display: flex;
                align-items: center;
                gap: 6px;
                list-style: none;
                font-family: var(--display);
                font-weight: 700
            }

            .nav ul a {
                display: inline-flex;
                min-height: 36px;
                align-items: center;
                padding: 7px 11px;
                border-radius: var(--bp-radius-pill);
                color: rgba(255, 255, 255, .78);
                font-size: .92rem;
                transition: background-color 180ms ease, color 180ms ease, transform 160ms var(--ease-out)
            }

            .hero {
                position: relative;
                min-height: 860px;
                display: grid;
                place-items: center;
                overflow: hidden;
                padding: 138px 0 80px;
                color: var(--bp-text);
                background: var(--bp-bg)
            }

            .hero::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, rgba(8, 12, 22, .97), rgba(8, 12, 22, .74) 45%, rgba(8, 12, 22, .92)), url('https://byruhaa.com/wp-content/uploads/2026/03/ChatGPT-Image-Mar-10-2026-10_54_44-AM.png') center/cover no-repeat;
                opacity: .88;
                pointer-events: none
            }

            .hero::after {
                content: "";
                position: absolute;
                inset: 72px 3vw 42px;
                border: 1px solid rgba(255, 255, 255, .05);
                border-radius: var(--bp-radius-xl);
                background: linear-gradient(rgba(255, 255, 255, .02) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .02) 1px, transparent 1px);
                background-size: 60px 60px;
                mask-image: radial-gradient(ellipse at center, black 38%, transparent 78%);
                pointer-events: none
            }

            .hero-ambient {
                position: absolute;
                width: 600px;
                height: 600px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(249, 164, 116, .08) 0%, rgba(249, 164, 116, .03) 40%, transparent 70%);
                top: 10%;
                right: -5%;
                pointer-events: none;
                filter: blur(60px);
                animation: ambientFloat 12s ease-in-out infinite alternate;
            }

            .hero-ambient-2 {
                position: absolute;
                width: 400px;
                height: 400px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(71, 214, 204, .05) 0%, transparent 60%);
                bottom: 15%;
                left: 5%;
                pointer-events: none;
                filter: blur(50px);
                animation: ambientFloat 16s ease-in-out 3s infinite alternate-reverse;
            }

            @keyframes ambientFloat {
                0% { transform: translate(0, 0) scale(1); }
                100% { transform: translate(20px, -20px) scale(1.08); }
            }

            .hero .wrap {
                position: relative;
                z-index: 2;
                display: grid;
                grid-template-columns: minmax(0, 1.1fr) minmax(300px, .64fr);
                grid-template-areas: "copy stats" "copy countdown";
                align-items: center;
                gap: 22px
            }

            .hero .kicker,
            .hero h1,
            .hero .lead,
            .hero p.desc,
            .hero-cta {
                grid-column: 1
            }

            .hero-stats {
                grid-area: stats
            }

            .countdown-card {
                grid-area: countdown
            }

            .hero .kicker {
                font-family: var(--display);
                font-size: .95rem;
                font-weight: 800;
                color: var(--bp-primary);
                margin-bottom: 14px
            }

            .hero h1 {
                font-size: clamp(3.5rem, 6vw, 5.5rem);
                line-height: 1.1;
                max-width: 840px;
                background: linear-gradient(180deg, #fff 0%, #fff 54%, #bdb5aa 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
                text-shadow: 0 24px 70px rgba(255, 255, 255, .08)
            }

            .hero h1 .by {
                display: block;
                margin-top: 14px;
                color: rgba(255, 255, 255, .76);
                font-size: 1.25rem;
                font-weight: 600;
                background: none;
                -webkit-text-fill-color: rgba(255, 255, 255, .76)
            }

            .hero .lead {
                margin: 20px 0 12px;
                color: var(--bp-primary);
                font-family: var(--amiri);
                font-size: clamp(1.6rem, 2.5vw, 2.2rem)
            }

            .hero p.desc {
                max-width: 760px;
                color: var(--bp-text-soft);
                font-size: 1.06rem;
                line-height: 1.82;
                margin-bottom: 24px
            }

            .hero-cta {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap
            }

            .hero-stats,
            .worry-grid,
            .masters-grid,
            .feat-grid,
            .penta,
            .weekend,
            .price-grid,
            .aff-grid {
                display: grid;
                gap: 16px
            }

            .hero-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .stat,
            .countdown-card,
            .worry-card,
            .master,
            .feat,
            .penta-col,
            .wcard,
            .after-card,
            .price-card,
            .aff-card,
            .reg-box,
            details {
                border: 1px solid var(--bp-border);
                border-radius: var(--bp-radius-xl);
                background: var(--bp-panel);
                box-shadow: 0 4px 24px rgba(0, 0, 0, .12)
            }

            .stat {
                min-height: 122px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                background: rgba(8, 14, 28, .72);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, .06)
            }

            .stat b {
                font-family: var(--display);
                font-size: 2.05rem;
                color: var(--bp-text);
                line-height: 1
            }

            .stat span {
                color: var(--bp-text-muted);
                font-size: .92rem
            }

            .countdown-card {
                padding: 22px;
                background: rgba(8, 14, 28, .78);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(249, 164, 116, .1);
                box-shadow: 0 0 40px rgba(249, 164, 116, .04)
            }

            .countdown-card .cd-label {
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-text);
                font-size: 1.02rem
            }

            .countdown-card .cd-note {
                margin: 6px 0 18px;
                color: var(--bp-primary);
                font-size: .9rem
            }

            .countdown {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px
            }

            .cd-unit {
                padding: 14px 6px;
                border: 1px solid rgba(255, 255, 255, .06);
                border-radius: var(--bp-radius-md);
                background: linear-gradient(180deg, rgba(255, 255, 255, .05), rgba(255, 255, 255, .02));
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .cd-unit::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(249, 164, 116, .2), transparent);
            }

            .cd-unit b {
                display: block;
                font-family: var(--display);
                font-size: 1.75rem;
                line-height: 1;
                color: var(--bp-text)
            }

            .cd-unit span {
                display: block;
                margin-top: 6px;
                color: var(--bp-text-muted);
                font-size: .75rem
            }

            .seats-pill {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin-top: 18px;
                padding: 10px 16px;
                border: 1px solid rgba(255, 107, 107, .3);
                border-radius: var(--bp-radius-pill);
                background: linear-gradient(135deg, rgba(255, 107, 107, .1), rgba(249, 164, 116, .06));
                color: var(--bp-text-soft);
                font-family: var(--display);
                font-size: .92rem;
                animation: urgencyPulse 2.5s ease-in-out infinite;
            }

            .seats-pill b {
                color: var(--bp-danger);
                font-size: 1.1em;
            }

            .seats-pill .live {
                background: var(--bp-danger) !important;
                box-shadow: 0 0 0 5px rgba(255, 107, 107, .15);
                animation: livePulse 1.8s ease-in-out infinite;
            }

            @keyframes urgencyPulse {
                0%, 100% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
                50% { box-shadow: 0 0 20px rgba(255, 107, 107, .1), 0 0 0 0 rgba(255, 107, 107, 0); }
            }

            @keyframes livePulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: .5; transform: scale(.8); }
            }

            .worry {
                margin-top: 0;
                padding-top: 80px
            }

            .worry-grid {
                grid-template-columns: 8fr 4fr;
                margin-top: 42px
            }

            .worry-card {
                padding: 28px;
                min-height: 220px
            }

            .worry-card:nth-child(2) {
                grid-row: span 2
            }

            .worry-card h3,
            .feat h3,
            .wcard h3 {
                font-size: 1.35rem;
                margin-bottom: 12px
            }

            .worry-card p,
            .feat p,
            .wcard li,
            .after-card p {
                color: var(--bp-text-muted)
            }

            .worry-card .sol {
                margin-top: 18px;
                padding: 14px 16px;
                border: 1px solid rgba(249, 164, 116, .14);
                border-radius: var(--bp-radius-lg);
                background: rgba(249, 164, 116, .05);
                color: var(--bp-text-soft)
            }

            .worry-card .sol b {
                color: var(--bp-primary)
            }

            .masters,
            .sakina,
            .pricing,
            .affiliate,
            .register,
            .faq,
            .after,
            .features,
            .day {
                background: var(--bp-bg)
            }

            .masters-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                margin-top: 42px
            }

            .master {
                padding: 28px;
                min-height: 320px;
                display: flex;
                flex-direction: column
            }

            .master .axis {
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-primary);
                font-size: .82rem;
                margin-bottom: 18px
            }

            .master .medallion {
                width: 72px;
                height: 72px;
                border-radius: var(--bp-radius-lg);
                display: grid;
                place-items: center;
                margin-bottom: 22px;
                border: 1px solid rgba(249, 164, 116, .24);
                background: linear-gradient(135deg, rgba(249, 164, 116, .15), rgba(195, 163, 255, .08));
                color: var(--bp-primary);
                font-family: var(--display);
                font-size: 1.7rem;
                font-weight: 800
            }

            .master h3 {
                font-size: 1.28rem
            }

            .master .role {
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-text-soft);
                font-size: .95rem;
                margin: 4px 0 14px
            }

            .master p {
                color: var(--bp-text-muted);
                flex: 1
            }

            .master .tag {
                margin-top: 20px;
                padding-top: 16px;
                border-top: 1px solid var(--bp-border);
                color: rgba(255, 255, 255, .48);
                font-size: .88rem
            }

            .feat-grid {
                grid-template-columns: repeat(12, 1fr);
                margin-top: 42px
            }

            .feat {
                padding: 28px;
                min-height: 260px
            }

            .feat:nth-child(1),
            .feat:nth-child(5) {
                grid-column: span 5
            }

            .feat:nth-child(2),
            .feat:nth-child(3) {
                grid-column: span 4
            }

            .feat:nth-child(4) {
                grid-column: span 3
            }

            .feat .num {
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-primary);
                font-size: .9rem;
                margin-bottom: 12px
            }

            .feat .quote {
                margin-top: 16px;
                padding: 13px 15px;
                border: 1px solid rgba(71, 214, 204, .16);
                border-radius: var(--bp-radius-lg);
                background: rgba(71, 214, 204, .05);
                color: #c9fffa;
                font-family: var(--amiri)
            }

            .penta {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                margin-top: 42px;
                text-align: start
            }

            .penta-col {
                padding: 22px
            }

            .penta-col h3 {
                color: var(--bp-primary);
                font-size: 1.05rem;
                text-align: center;
                margin-bottom: 14px
            }

            .penta-col ul,
            .wcard ul,
            .aff-card ul {
                list-style: none;
                display: grid;
                gap: 8px
            }

            .penta-col li {
                padding: 7px 0;
                border-bottom: 1px solid var(--bp-border);
                color: var(--bp-text-muted);
                font-size: .93rem
            }

            .penta-col li:last-child {
                border-bottom: 0
            }

            .timeline {
                position: relative;
                max-width: 900px;
                margin: 46px auto 0;
                padding: 10px 28px;
                border-inline-start: 1px solid rgba(249, 164, 116, .26)
            }

            .tl-item {
                position: relative;
                display: grid;
                grid-template-columns: 180px 1fr;
                gap: 18px;
                padding: 12px 0
            }

            .tl-item::before {
                content: "";
                position: absolute;
                inset-inline-start: -34px;
                top: 24px;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: var(--bp-primary);
                box-shadow: 0 0 0 6px rgba(249, 164, 116, .1)
            }

            .tl-item .time {
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-primary);
                line-height: 1.55
            }

            .tl-item .act {
                color: var(--bp-text-soft)
            }

            .weekend {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-top: 34px
            }

            .wcard {
                padding: 26px
            }

            .wcard li {
                position: relative;
                padding-inline-start: 18px
            }

            .wcard li::before {
                content: "";
                position: absolute;
                inset-inline-start: 0;
                top: .92em;
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: var(--bp-primary)
            }

            .after-card {
                display: grid;
                grid-template-columns: 7fr 5fr;
                gap: 24px;
                align-items: center;
                padding: 28px;
                margin-top: 38px
            }

            .after-card h3 {
                font-size: 1.65rem;
                margin-bottom: 14px
            }

            .after-card .chips {
                display: flex;
                flex-wrap: wrap;
                gap: 9px;
                margin-top: 18px
            }

            .after-card .chips span {
                display: inline-flex;
                min-height: 32px;
                align-items: center;
                padding: 6px 11px;
                border: 1px solid rgba(249, 164, 116, .14);
                border-radius: var(--bp-radius-pill);
                background: rgba(249, 164, 116, .05);
                color: var(--bp-primary);
                font-family: var(--display);
                font-weight: 800;
                font-size: .84rem
            }

            .after-img {
                overflow: hidden;
                border-radius: var(--bp-radius-lg);
                min-height: 320px;
                border: 1px solid var(--bp-border)
            }

            .after-img img {
                width: 100%;
                height: 100%;
                min-height: 320px;
                object-fit: cover;
                filter: saturate(.9) contrast(1.04)
            }

            .price-deadline {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 10px;
                padding: 8px 13px;
                border: 1px solid rgba(244, 191, 99, .22);
                border-radius: var(--bp-radius-pill);
                background: rgba(244, 191, 99, .06);
                color: var(--bp-warning);
                font-family: var(--display);
                font-weight: 800
            }

            .price-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                margin-top: 36px
            }

            .price-card {
                position: relative;
                overflow: hidden;
                padding: 30px 24px;
                min-height: 240px
            }

            .price-card.feat-card {
                border-color: rgba(249, 164, 116, .32);
                box-shadow: var(--bp-glow-strong);
                background: linear-gradient(135deg, rgba(22, 40, 68, 1), rgba(30, 50, 82, 1));
            }

            .price-card.feat-card::after {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 2px;
                background: linear-gradient(90deg, transparent, var(--bp-primary), transparent);
            }

            .price-card .ribbon {
                position: absolute;
                top: 16px;
                inset-inline-end: 16px;
                padding: 5px 12px;
                border-radius: var(--bp-radius-pill);
                background: linear-gradient(135deg, var(--bp-primary), #F7B38E);
                color: #1A120D;
                font-family: var(--display);
                font-weight: 800;
                font-size: .75rem;
                box-shadow: 0 2px 8px rgba(249, 164, 116, .3)
            }

            .price-card .plan {
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-primary);
                margin-bottom: 12px
            }

            .price-card .amount {
                font-family: var(--display);
                font-size: 2.5rem;
                font-weight: 800;
                line-height: 1.1
            }

            .price-card .amount small {
                font-size: 1rem;
                color: var(--bp-text-muted)
            }

            .price-card .full {
                margin-top: 8px;
                color: rgba(255, 255, 255, .44);
                text-decoration: line-through
            }

            .price-card .save {
                margin-top: 12px;
                color: var(--bp-warning);
                font-weight: 800
            }

            .price-card .cond {
                margin-top: 8px;
                color: var(--bp-text-muted);
                font-size: .94rem
            }

            .aff-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-top: 38px
            }

            .aff-card {
                padding: 30px
            }

            .aff-card h3 {
                font-size: 1.3rem;
                margin-bottom: 12px
            }

            .aff-card .big {
                font-family: var(--display);
                font-size: 2.2rem;
                font-weight: 800;
                color: var(--bp-primary);
                margin-bottom: 12px
            }

            .aff-card p,
            .aff-card li {
                color: var(--bp-text-muted)
            }

            .aff-card li {
                position: relative;
                padding-inline-start: 18px
            }

            .aff-card li::before {
                content: "";
                position: absolute;
                inset-inline-start: 0;
                top: .92em;
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: var(--bp-primary)
            }

            .aff-cta {
                text-align: center;
                margin-top: 30px
            }

            .reg-box {
                display: grid;
                grid-template-columns: 5fr 7fr;
                margin-top: 42px;
                overflow: hidden
            }

            .reg-info {
                padding: 34px;
                border-inline-end: 1px solid var(--bp-border);
                background: rgba(255, 255, 255, .025)
            }

            .reg-info h3,
            .reg-form h3 {
                font-size: 1.4rem;
                margin-bottom: 12px
            }

            .reg-info p,
            .reg-form .micro {
                color: var(--bp-text-muted)
            }

            .point {
                display: flex;
                gap: 10px;
                align-items: flex-start;
                padding: 11px 0;
                border-top: 1px solid var(--bp-border);
                color: var(--bp-text-soft)
            }

            .point i {
                color: var(--bp-primary);
                font-style: normal
            }

            .reg-form {
                padding: 34px
            }

            .reg-form a:not(.btn) {
                display: inline-flex;
                min-height: 36px;
                align-items: center;
                color: var(--bp-primary) !important;
                font-family: var(--display);
                font-weight: 800
            }

            .faq-list {
                max-width: 920px;
                margin: 40px auto 0
            }

            .faq-cat {
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-primary);
                font-size: 1rem;
                margin: 28px 0 12px
            }

            details {
                overflow: hidden;
                margin-bottom: 10px
            }

            summary {
                list-style: none;
                cursor: pointer;
                padding: 18px 20px;
                font-family: var(--display);
                font-weight: 800;
                color: var(--bp-text);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px
            }

            summary::-webkit-details-marker {
                display: none
            }

            summary::after {
                content: "+";
                flex: none;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                display: grid;
                place-items: center;
                border: 1px solid var(--bp-border);
                color: var(--bp-primary);
                transition: transform 180ms var(--ease-out), background-color 180ms ease
            }

            details[open] summary::after {
                content: "−";
                background: rgba(249, 164, 116, .08)
            }

            .ans {
                padding: 0 20px 20px;
                color: var(--bp-text-muted);
                border-top: 1px solid var(--bp-border)
            }

            .foot {
                background: #040810;
                color: var(--bp-text);
                text-align: center;
                padding: 72px 0 36px;
                border-top: 1px solid rgba(249, 164, 116, .08);
                position: relative;
            }

            .foot::before {
                content: "";
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 400px;
                height: 200px;
                background: radial-gradient(ellipse, rgba(249, 164, 116, .04) 0%, transparent 70%);
                pointer-events: none;
            }

            .foot img {
                height: 56px;
                margin: 0 auto 22px;
                filter: brightness(0) invert(1);
                opacity: .88
            }

            .foot h3 {
                font-size: 1.36rem;
                margin-bottom: 10px
            }

            .foot p {
                color: var(--bp-text-muted);
                margin-bottom: 8px
            }

            .foot .copy {
                margin-top: 28px;
                padding-top: 22px;
                border-top: 1px solid rgba(255, 255, 255, .06);
                color: rgba(255, 255, 255, .38);
                font-size: .86rem
            }

            .wa {
                position: fixed;
                inset-block-end: 20px;
                inset-inline-start: 20px;
                z-index: 70;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 48px;
                padding: 10px 16px;
                border-radius: var(--bp-radius-pill);
                background: linear-gradient(135deg, #59d9a6, #47d6cc);
                color: #061313;
                font-family: var(--display);
                font-weight: 800;
                box-shadow: var(--bp-shadow-sm);
                transition: transform 160ms var(--ease-out), box-shadow 180ms ease
            }

            .wa:active {
                transform: scale(.97)
            }

            .reveal {
                opacity: 0;
                transform: translateY(22px);
                transition: opacity 480ms var(--ease-out), transform 580ms var(--ease-out)
            }

            .reveal.in {
                opacity: 1;
                transform: translateY(0)
            }

            @media (hover:hover) and (pointer:fine) {

                .btn:hover,
                .topbar a.mini-cta:hover,
                .nav ul a:hover,
                .wa:hover {
                    transform: translateY(-2px)
                }

                .btn-gold:hover {
                    box-shadow: var(--bp-shadow-xs), inset rgba(255, 255, 255, .44) 0 6px 0 -5px, rgba(249, 164, 116, .7) 0 8px 24px -6px;
                }

                .btn-gold:hover::before {
                    opacity: 1;
                }

                .btn-ghost:hover,
                .nav ul a:hover {
                    background: rgba(255, 255, 255, .07);
                    border-color: rgba(255, 255, 255, .14);
                    color: #fff
                }

                .stat:hover,
                .worry-card:hover,
                .master:hover,
                .feat:hover,
                .penta-col:hover,
                .wcard:hover,
                .price-card:hover,
                .aff-card:hover,
                details:hover {
                    border-color: rgba(249, 164, 116, .16);
                    box-shadow: var(--bp-glow), 0 12px 40px rgba(0, 0, 0, .2);
                    transform: translateY(-5px)
                }
            }

            .stat,
            .worry-card,
            .master,
            .feat,
            .penta-col,
            .wcard,
            .price-card,
            .aff-card,
            details {
                transition: transform 320ms var(--ease-out), border-color 240ms ease, box-shadow 360ms var(--ease-out), background-color 240ms ease
            }

            @media(max-width:1100px) {
                .hero {
                    min-height: auto
                }

                .hero .wrap {
                    grid-template-columns: 1fr;
                    grid-template-areas: "copy" "stats" "countdown"
                }

                .hero .kicker,
                .hero h1,
                .hero .lead,
                .hero p.desc,
                .hero-cta {
                    grid-column: 1
                }

                .hero-stats {
                    max-width: 760px
                }

                .feat-grid,
                .price-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr))
                }

                .feat,
                .feat:nth-child(n) {
                    grid-column: auto
                }

                .penta {
                    grid-template-columns: repeat(3, minmax(0, 1fr))
                }

                .worry-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr))
                }

                .worry-card:nth-child(2) {
                    grid-row: auto
                }
            }

            @media(max-width:860px) {
                section {
                    padding: 76px 0
                }

                .wrap {
                    width: min(100% - 32px, 1240px)
                }

                .topbar .wrap {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 2px;
                    padding: 5px 0;
                    text-align: center;
                    font-size: .7rem;
                    line-height: 1.25
                }

                .topbar .dot {
                    display: none
                }

                .topbar a.mini-cta {
                    margin-inline-start: 0;
                    justify-self: center;
                    min-height: 28px;
                    padding: 4px 10px;
                    font-size: .72rem
                }

                .nav {
                    top: 62px
                }

                .nav .wrap {
                    justify-content: center;
                    padding-top: 6px
                }

                .nav ul {
                    display: none
                }

                .nav .logo {
                    height: 32px
                }

                .hero {
                    padding: 80px 0 12px;
                    text-align: center
                }

                .hero::after {
                    inset: 86px 16px 30px;
                    border-radius: var(--bp-radius-lg)
                }

                .hero h1 {
                    font-size: 2.4rem;
                    line-height: 1.15
                }

                .hero h1 .by {
                    font-size: 1rem;
                    margin-top: 6px
                }

                .hero .lead {
                    font-size: 1.3rem;
                    margin: 10px 0 6px
                }

                .hero p.desc {
                    font-size: .84rem;
                    line-height: 1.48;
                    margin-bottom: 10px
                }

                .hero-cta {
                    justify-content: center;
                    gap: 8px;
                    grid-row: 5
                }

                .hero-cta .btn {
                    min-height: 36px;
                    padding: 7px 12px;
                    font-size: .82rem;
                    width: auto
                }

                .hero-stats {
                    grid-row: 6;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 6px;
                    margin-top: 8px
                }

                .stat {
                    min-height: 52px;
                    padding: 7px
                }

                .stat b {
                    font-size: 1.12rem
                }

                .stat span {
                    font-size: .68rem
                }

                .countdown-card {
                    grid-row: 7;
                    padding: 7px
                }

                .countdown-card .cd-label {
                    font-size: .76rem
                }

                .countdown-card .cd-note {
                    font-size: .68rem;
                    margin-bottom: 6px
                }

                .countdown {
                    gap: 5px
                }

                .cd-unit {
                    padding: 5px 2px
                }

                .cd-unit b {
                    font-size: .95rem
                }

                .cd-unit span {
                    font-size: .58rem
                }

                .seats-pill {
                    font-size: .68rem;
                    padding: 5px 8px;
                    margin-top: 7px
                }

                .worry {
                    margin-top: 24px;
                    padding-top: 32px
                }

                .worry-grid,
                .masters-grid,
                .weekend,
                .after-card,
                .aff-grid,
                .reg-box {
                    grid-template-columns: 1fr
                }

                .feat-grid,
                .price-grid,
                .penta {
                    grid-template-columns: 1fr
                }

                .after-img,
                .after-img img {
                    min-height: 240px
                }

                .timeline {
                    padding-inline-start: 24px
                }

                .tl-item {
                    grid-template-columns: 1fr;
                    gap: 3px
                }

                .sec-title {
                    font-size: 2.2rem
                }

                .sec-sub {
                    font-size: 1rem
                }

                .reg-info {
                    border-inline-end: 0;
                    border-bottom: 1px solid var(--bp-border)
                }

                .reg-info,
                .reg-form {
                    padding: 26px 20px
                }

                .btn:not(.hero-cta .btn) {
                    width: 100%
                }

                .wa {
                    width: 48px;
                    padding: 0
                }

                .wa span {
                    display: none
                }
            }

            @media(max-width:430px) {
                .wrap {
                    width: min(100% - 28px, 1240px)
                }

                .hero {
                    padding-top: 76px
                }

                .hero h1 {
                    font-size: 2.1rem
                }

                .hero .kicker {
                    font-size: .8rem
                }

                .hero-stats {
                    gap: 7px
                }

                .stat {
                    min-height: 68px
                }

                .countdown-card {
                    border-radius: var(--bp-radius-lg)
                }

                .sec-title {
                    font-size: 1.9rem
                }

                .price-card,
                .aff-card,
                .worry-card,
                .feat,
                .master,
                .wcard {
                    padding: 22px
                }
            }

            @media (prefers-reduced-motion:reduce) {
                html {
                    scroll-behavior: auto
                }

                .reveal {
                    opacity: 1;
                    transform: none;
                    transition: opacity 160ms ease
                }

                .btn,
                .topbar a.mini-cta,
                .nav ul a,
                .wa,
                .stat,
                .worry-card,
                .master,
                .feat,
                .penta-col,
                .wcard,
                .price-card,
                .aff-card,
                details,
                summary::after {
                    transition: color 160ms ease, background-color 160ms ease, border-color 160ms ease, box-shadow 160ms ease
                }

                .btn:active,
                .wa:active {
                    transform: none
                }
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
        <div class="hero-ambient"></div>
        <div class="hero-ambient-2"></div>
        <nav class="nav">
            <div class="wrap">
                <a href="https://byruhaa.com"><img class="logo"
                        src="https://byruhaa.com/wp-content/uploads/2023/02/باللون-الأبيض-شعار-بيرحاء-الجديد-0٤-scaled.png"
                        alt="بيرحاء"></a>
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
            <p class="desc">تجربةٌ تعليميةٌ مكثَّفة في قلب الطبيعة — تجمع القرآن الكريم، والنحوَ بالفطرة، ومهاراتِ
                الحياة، وتُحضِّر الجيل لمستقبلٍ يتغيّر بسرعة البرق — في بيئةٍ آمنةٍ ملهمة.</p>

            <div class="hero-stats">
                <div class="stat"><b>٣٠</b><span>يوماً متصلاً</span></div>
                <div class="stat"><b>١٠</b><span>مقعداً فقط</span></div>
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
                <div class="seats-pill"><span class="live"></span> المقاعد المتبقّية محدودة — <b id="seatsLeft">٠</b>
                    مقعداً</div>
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
                    <p>المشهد الدولي متقلّب، والسفر بات حِملاً ثقيلاً على الأسر. برنامجنا في إبراء — على أرضك، تحت
                        سمائك، بعيداً عن مخاطر الخارج.</p>
                    <div class="sol"><b>الحل:</b> تجربةٌ استثنائية داخل عُمان — الأمان الكامل مع المنهج الثريّ.</div>
                </div>
                <div class="worry-card reveal">
                    <h3>خوف بقاء الأبناء دون إشراف</h3>
                    <p>ثلاثة أشهرٍ إجازة، والابن أمام الشاشة طوال اليوم. لا توجيه، لا إنتاج، لا بناء — هذا ليس راحةً، بل
                        إهدارٌ لأثمن مراحل التكوين.</p>
                    <div class="sol"><b>الحل:</b> ٣٠ يوماً منظّمة بالدقيقة — إشرافٌ تام، وولي الأمر على علمٍ بكل شيء.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════ جديد: ثلاثة أعلام تقود الرحلة ════════ -->
    <section class="masters" id="masters">
        <div class="wrap center">
            <span class="eyebrow">ما لم تكشفه الصفحة من قبل</span>
            <h2 class="sec-title">ثلاثة أعلامٍ تقود الرحلة</h2>
            <p class="sec-sub">لا برنامجٌ عام — بل ثلاثة محاور، يقود كلّ محورٍ منها عَلَمٌ في فنّه، يرافق القادة طوال
                الشهر.</p>

            <div class="masters-grid" style="text-align:start">
                <div class="master reveal">
                    <div class="axis">المحور الأول · حفظ القرآن</div>
                    <div class="medallion">إ.غ</div>
                    <h3>الأستاذ إبراهيم الغافري</h3>
                    <div class="role">حفظ القرآن بطريقةٍ إبداعية</div>
                    <p>مؤسّس برنامج «مكنون» — أشهر برامج حفظ القرآن في عُمان، ومضى عليه نحو عشر سنوات، تخرّج منه مئاتُ
                        الحُفّاظ المُتقنين.</p>
                    <div class="tag">يُشرف على ملف القرآن في البرنامج</div>
                </div>

                <div class="master reveal">
                    <div class="axis">المحور الثاني · النحو العربي</div>
                    <div class="medallion">أ.ص</div>
                    <h3>د. أحمد صوان</h3>
                    <div class="role">النحوُ بالفطرة — تنظيراً وتطبيقاً</div>
                    <p>تلميذ د. عبدالله الدنّان ووريثه الأول في تعليم العربية الفصيحة بالفطرة، حائزُ جائزة الشيخ خليفة
                        بن زايد في قصص الأطفال، ومحاضرٌ في جامعات سوريا ومصر وتركيا. يرافق القادة الشهرَ كاملاً.</p>
                    <div class="tag">يُلازم القادة طوال الشهر — بإذن الله</div>
                </div>

                <div class="master reveal">
                    <div class="axis">المحور الثالث · مهارات الحياة</div>
                    <div class="medallion">أ.ب</div>
                    <h3>أبو بلج عبدالله العيسري</h3>
                    <div class="role">خماسية السكينة — تنظيراً وتطبيقاً</div>
                    <p>يَنقل عُصارةَ خبرةٍ تتجاوز رُبع قرن في تعليم «خماسية السكينة» وما يتصل بها، استفاد من برامجه
                        أكثرُ من ٤٠٬٠٠٠ منتسب.</p>
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
            <p class="sec-sub">بُنيت من خبرةٍ ميدانيةٍ متراكمة ومن دراسات OECD ومنتدى الاقتصاد العالمي في مهارات
                المستقبل.</p>
            <div class="feat-grid" style="text-align:start">
                <div class="feat reveal">
                    <div class="num">١ — الأصل والمرساة</div>
                    <h3>القرآن والحياة في برنامجٍ واحدٍ متكامل</h3>
                    <p>يُقدَّم القرآن مادةً مستقلةً بأوجه التعامل الستة: الاستماع، والحفظ، والتلاوة، والتدبّر،
                        والامتثال، والتبليغ — وهو في الوقت ذاته النورُ الذي يَغشى كلّ شيء.</p>
                    <div class="quote">لسنا نُعلِّم القرآن ثم الحياة — بل نُعلِّمهما معاً بلا فصل.</div>
                </div>
                <div class="feat reveal">
                    <div class="num">٢ — منهجية موثّقة</div>
                    <h3>تحضير الجيل للمستقبل بأدواتٍ حقيقية</h3>
                    <p>٣٩٪ من مهارات اليوم ستتغيّر جذرياً بحلول ٢٠٣٠. نُترجم ذلك إلى منهجٍ يوميّ يعيشه الطالب — التفكير
                        النقدي، والإبداع، والتكيّف، والذكاء الاصطناعي.</p>
                    <div class="quote">«المستقبل لن يكافئ أصحاب الشهادات — بل أصحاب المهارات التي تتكيّف» — WEF 2025
                    </div>
                </div>
                <div class="feat reveal">
                    <div class="num">٣ — علم نفس تطبيقي</div>
                    <h3>بناء الهوية في سنّ الهشاشة بأمانٍ حقيقي</h3>
                    <p>الصفوف ٧–٩ مرحلةُ الهوية الأكثر حساسية. نضع هذه الهشاشة في صميم التصميم: جلساتٌ حوارية، ومهاراتُ
                        ضبط النفس، وبيئةٌ آمنة يكتشف فيها الطالب نفسه دون خوف.</p>
                    <div class="quote">McKinsey: المهارات الاجتماعية والعاطفية بين أعلى المهارات طلباً حتى ٢٠٣٠.</div>
                </div>
                <div class="feat reveal">
                    <div class="num">٤ — تجربة ريادية</div>
                    <h3>مشروعٌ حقيقيّ ينتهي بنتاجٍ ملموس</h3>
                    <p>كلّ طالبٍ يُغادر بمشروعٍ شخصيٍّ طوّره خلال الشهر: فكرة، خطة، وعرضٌ أمام أقرانه ومعلّميه — تدريبٌ
                        على التفكير الريادي الذي يُعدّه OECD ركيزةً لوظائف المستقبل.</p>
                    <div class="quote">التعلّم بالمشاريع يرفع الاحتفاظ بالمهارات ٧٥٪ مقارنةً بالتلقين.</div>
                </div>
                <div class="feat reveal">
                    <div class="num">٥ — استثمار العمر</div>
                    <h3>برنامج الاستمرارية السنوي — البدايةُ لا النهاية</h3>
                    <p>بعد الشهر، يدخل الطالب برنامجاً يمتدّ العامَ كلّه: جلساتٌ أسبوعية عن بُعد، ومتابعةٌ شهرية،
                        ولقاءاتٌ فصلية حضورية تُرسِّخ التغيير.</p>
                    <div class="quote">من تجربةٍ صيفية — إلى مجتمع تعلّمٍ مستدام يرافق الطالب عاماً كاملاً.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════ خماسية السكينة ════════ -->
    <section class="sakina">
        <div class="wrap center">
            <span class="eyebrow">فلسفة البرنامج</span>
            <h2 class="sec-title">خماسية السكينة</h2>
            <p class="sec-sub">خمسةُ أبعادٍ متوازنة، لكلّ بُعدٍ خمسةُ فروع — كلّ يومٍ في البرنامج مُصمَّمٌ ليلمسها
                جميعاً.</p>
            <div class="penta">
                <div class="penta-col reveal">
                    <h3>١ — عبادة</h3>
                    <ul>
                        <li>الإيمان</li>
                        <li>الإحسان</li>
                        <li>قول الحسن</li>
                        <li>الصلاة</li>
                        <li>الإنفاق</li>
                    </ul>
                </div>
                <div class="penta-col reveal">
                    <h3>٢ — علم</h3>
                    <ul>
                        <li>القرآن</li>
                        <li>البيان</li>
                        <li>الشرعية والكونية</li>
                        <li>القراءة</li>
                        <li>الكتابة</li>
                    </ul>
                </div>
                <div class="penta-col reveal">
                    <h3>٣ — عمل</h3>
                    <ul>
                        <li>الزراعة</li>
                        <li>التجارة</li>
                        <li>التثمير والادخار</li>
                        <li>العمل المنزلي</li>
                        <li>التطوّع</li>
                    </ul>
                </div>
                <div class="penta-col reveal">
                    <h3>٤ — لعب</h3>
                    <ul>
                        <li>الرتع</li>
                        <li>ألعاب المحاكاة</li>
                        <li>الألعاب الحركية</li>
                        <li>الألعاب التقنية</li>
                        <li>الألعاب التمثيلية</li>
                    </ul>
                </div>
                <div class="penta-col reveal">
                    <h3>٥ — نوم وصحة</h3>
                    <ul>
                        <li>قبل النوم</li>
                        <li>النوم</li>
                        <li>بعد الاستيقاظ</li>
                        <li>الغذاء</li>
                        <li>الصحة</li>
                    </ul>
                </div>
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
                <div class="tl-item">
                    <div class="time">٠٣:٢٠ فجراً</div>
                    <div class="act">التهجّد</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٤:٠٠ — ٠٦:٠٠</div>
                    <div class="act">قرآن الفجر وأذكار الصباح وصلاة الضحى (مع مراجعة المحفوظ)</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٦:٠٠ — ٠٦:٣٠</div>
                    <div class="act">الرياضة الصباحية</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٦:٣٠ — ٠٧:٣٠</div>
                    <div class="act">إعداد الإفطار وتناوله (بمشاركة القادة)</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٨:٣٠ — ١١:٣٠</div>
                    <div class="act">الحلقات: تعليم القرآن، والبيان، ومهارات الحياة</div>
                </div>
                <div class="tl-item">
                    <div class="time">١١:٣٠ — ١٢:٠٠</div>
                    <div class="act">نصف ساعةٍ للتقنية (والذكاء الاصطناعي)</div>
                </div>
                <div class="tl-item">
                    <div class="time">١٢:٠٠ — ٠١:٠٠</div>
                    <div class="act">صلاة الظهر</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠١:٠٠ — ٠٢:٠٠</div>
                    <div class="act">وجبة الغداء (بمشاركة القادة)</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٢:٠٠ — ٠٣:٣٠</div>
                    <div class="act">وقتٌ مفتوح (نوم · لعب · اتصالٌ بالأهل وفق الضوابط)</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٤:٠٠ — ٠٤:٣٠</div>
                    <div class="act">صلاة العصر جماعةً ومراجعة الحفظ</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٥:٠٠ — ٠٦:٠٠</div>
                    <div class="act">الرياضة المسائية</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٧:٠٠ — ٠٨:٣٠</div>
                    <div class="act">المغرب والعشاء</div>
                </div>
                <div class="tl-item">
                    <div class="time">٠٩:٠٠ — ١٠:٠٠</div>
                    <div class="act">وقتٌ مفتوح والاستعداد للنوم</div>
                </div>
                <div class="tl-item">
                    <div class="time">١٠:٠٠ م — ٠٣:٠٠ ص</div>
                    <div class="act">النوم الإلزامي (يُمنع: إضاءة، حديث، هواتف)</div>
                </div>
            </div>

            <div class="weekend">
                <div class="wcard reveal">
                    <h3>الجمعة</h3>
                    <ul>
                        <li>قرآن الصباح وإفطارٌ جماعيّ مميّز</li>
                        <li>رحلةٌ استكشافية خارج المخيم</li>
                        <li>صلاة الجمعة ونشاطٌ ترفيهيّ جماعي</li>
                        <li>عشاءٌ في الهواء الطلق</li>
                    </ul>
                </div>
                <div class="wcard reveal">
                    <h3>السبت</h3>
                    <ul>
                        <li>برنامج مغامرةٍ أو زيارةٍ ميدانية</li>
                        <li>ورشةٌ تطبيقية من اختيار الطلاب</li>
                        <li>وقتٌ حرٌّ ممتد واتصالٌ بالأهل</li>
                        <li>سهرةٌ ختامية: قصصٌ وأناشيدُ وتكريم</li>
                    </ul>
                </div>
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
                    <p>لأنّ التغيير الحقيقي يحتاج أكثر من ثلاثين يوماً، يلتحق كلّ خريجٍ ببرنامجٍ سنويّ مكمّل يرافقه طوال
                        العام الدراسي — ليس درساً إضافياً، بل مجتمعَ تعلّمٍ يُبقي الروابط حيّةً والهممَ مشتعلة.</p>
                    <div class="chips">
                        <span>جلسات أسبوعية عن بُعد</span><span>متابعة شهرية للمشروع</span>
                        <span>لقاءات فصلية حضورية</span><span>مجموعة أولياء الأمور</span><span>معسكر ختامي سنوي</span>
                    </div>
                </div>
                <div class="after-img">
                    <img src="https://byruhaa.com/wp-content/uploads/2026/03/ChatGPT-Image-Mar-10-2026-10_54_44-AM.png"
                        alt="ما بعد البرنامج">
                </div>
            </div>
        </div>
    </section>

    <!-- ════════ جديد: الرسوم والعروض ════════ -->
    <section class="pricing" id="pricing">
        <div class="wrap center">
            <span class="eyebrow">الرسوم والعروض</span>
            <h2 class="sec-title">استثمارٌ في عُمر ابنك — لا نفقةٌ تمضي</h2>
            <div class="price-deadline"><span class="live"
                    style="width:9px;height:9px;border-radius:50%;background:#ff6b6b;display:inline-block"></span> عروض
                التسجيل المبكر تنتهي ٣٠ يونيو — أو بنفاد المقاعد</div>

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
                    <div class="big">٣٠ ر.ع<span style="font-size:1rem;color:rgba(255,255,255,.8)"> فأكثر / لكل
                            مُسجَّل</span></div>
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
                <div class="reg-form"
                    style="display:flex;flex-direction:column;justify-content:center;align-items:flex-start;gap:18px">
                    <h3>تابع الحجز عبر صفحة الفعالية</h3>
                    <p class="micro" style="text-align:start;margin-top:0">استكمل الطلب من خلال النظام، ثم يتابع
                        الفريق معك خطوات القبول والتوقيع والسداد.</p>
                    <a href="{{ $registrationUrl }}" class="btn btn-gold">اذهب إلى صفحة الحجز</a>
                    <a href="{{ $eventUrl }}"
                        style="color:var(--teal);font-family:var(--display);font-weight:600">عرض تفاصيل الفعالية</a>
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
            <details open>
                <summary>متى يُقام البرنامج وأين؟</summary>
                <div class="ans">في مخيم بيرحاء بولاية إبراء، سلطنة عُمان — من <b>١ إلى ٣٠ يوليو ٢٠٢٦</b>، داخل
                    الأراضي العُمانية بلا سفرٍ ولا تأشيرات.</div>
            </details>
            <details>
                <summary>لمن يُوجَّه البرنامج؟</summary>
                <div class="ans">لطلاب الصفوف السابع والثامن والتاسع (نحو ١٣–١٥ سنة) من الفتيان العُمانيين في نسخته
                    الأولى.</div>
            </details>
            <details>
                <summary>كم عدد المقاعد؟ ولماذا محدودة؟</summary>
                <div class="ans">خمسون مقعداً فقط — التزاماً بجودة التجربة ونسبة إشرافٍ عالية لكل قائد، لا قراراً
                    تسويقياً.</div>
            </details>
            <details>
                <summary>كيف أُسجِّل وما خطوات القبول؟</summary>
                <div class="ans">تعبئة الاستمارة في الموقع، ثم فرز الطلبات بالأولوية، فلقاءٌ تعريفي، فالقبول والعقد،
                    ثم السداد — ليُصبح الطالب قائداً رسمياً.</div>
            </details>

            <div class="faq-cat">الرسوم والأمان</div>
            <details>
                <summary>كم تكلفة البرنامج؟</summary>
                <div class="ans">٧٠٠ ر.ع كاملاً، وتتوفّر عروض مبكرة: <b>٤٨٩</b> للفرد، <b>٤٦٩</b> للإخوة، <b>٤٥٩</b>
                    للمجموعة — تنتهي ٣٠ يونيو.</div>
            </details>
            <details>
                <summary>هل البرنامج آمنٌ لابني؟</summary>
                <div class="ans">نعم — داخل عُمان، بإشرافٍ تامٍّ على مدار الساعة، وجدولٍ لا يترك لحظةً دون رعاية،
                    وولي الأمر على اطّلاعٍ بكل شيء.</div>
            </details>
            <details>
                <summary>هل أتواصل مع ابني خلال البرنامج؟</summary>
                <div class="ans">نعم، في الوقت المفتوح اليومي (٢:٠٠–٣:٣٠) وجزءٍ من السبت. للتواصل مع الفريق: واتساب
                    96874155123.</div>
            </details>
            <p style="text-align:center;margin-top:22px;color:var(--muted)">للأسئلة كاملةً، تواصل معنا عبر <a
                    href="https://wa.me/96874155123" style="color:var(--teal);font-weight:600">الواتساب</a>.</p>
        </div>
    </section>

    <!-- ════════ التذييل ════════ -->
    <footer class="foot">
        <div class="wrap">
            <img src="https://byruhaa.com/wp-content/uploads/2023/02/باللون-الأبيض-شعار-بيرحاء-الجديد-0٤-scaled.png"
                alt="بيرحاء">
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
        (function() {
            // تحويل الأرقام إلى هندية-عربية
            const map = {
                '0': '٠',
                '1': '١',
                '2': '٢',
                '3': '٣',
                '4': '٤',
                '5': '٥',
                '6': '٦',
                '7': '٧',
                '8': '٨',
                '9': '٩'
            };
            const ar = n => String(n).replace(/[0-9]/g, d => map[d]);

            /* ════════ المبرّم: اضبط هذين السطرين ════════ */
            const target = new Date('2026-07-01T00:00:00+04:00').getTime(); // انطلاق الدفعة (توقيت عُمان)
            let seatsLeft = 10; // المقاعد المتبقّية (قابلة للتعديل)
            /* ═══════════════════════════════════════════ */

            const seatsEl = document.getElementById('seatsLeft');
            if (seatsEl) seatsEl.textContent = ar(seatsLeft);

            const cd = document.getElementById('countdown');
            const mini = document.getElementById('miniCount');
            const dEl = cd && cd.querySelector('[data-d]'),
                hEl = cd && cd.querySelector('[data-h]'),
                mEl = cd && cd.querySelector('[data-m]'),
                sEl = cd && cd.querySelector('[data-s]');

            function tick() {
                const diff = target - Date.now();
                if (diff <= 0) {
                    if (cd) cd.innerHTML =
                        '<div style="color:#fff;font-family:Aref Ruqaa">انطلقت الدفعة الأولى — بإذن الله 🌿</div>';
                    if (mini) mini.textContent = 'انطلق البرنامج';
                    return;
                }
                const d = Math.floor(diff / 864e5),
                    h = Math.floor(diff % 864e5 / 36e5),
                    m = Math.floor(diff % 36e5 / 6e4),
                    s = Math.floor(diff % 6e4 / 1e3);
                if (dEl) {
                    dEl.textContent = ar(d);
                    hEl.textContent = ar(h);
                    mEl.textContent = ar(m);
                    sEl.textContent = ar(s);
                }
                if (mini) mini.textContent = ar(d) + ' يوم · ' + ar(h) + ' س · ' + ar(m) + ' د';
            }
            tick();
            setInterval(tick, 1000);

            // كشفٌ عند التمرير
            const io = new IntersectionObserver((ents) => {
                ents.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('in');
                        io.unobserve(e.target);
                    }
                });
            }, {
                threshold: .12
            });
            document.querySelectorAll('.reveal').forEach(el => io.observe(el));
        })();
    </script>
</body>

</html>
