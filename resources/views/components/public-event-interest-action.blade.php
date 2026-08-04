@props([
    'event',
    'buttonClass' =>
        'inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#009060] px-6 py-3 text-sm font-bold text-white shadow-sm shadow-[#123329]/10 transition hover:-translate-y-0.5 hover:bg-[#007a52] active:translate-y-px dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#f0c63c]',
])

<div {{ $attributes->class('inline-flex shrink-0 overflow-visible min-w-max') }}>
    @if ($event->canBook())
        <a href="{{ route('customer.events.show', $event) }}"
            class="{{ $buttonClass }} whitespace-nowrap overflow-visible shrink-0 min-w-max">
            <span>احجز الآن</span>
            <x-hugeicon name="ticket-01" class="text-lg" />
        </a>
    @elseif (session('event_interest_recorded') === $event->id)
        <div role="status"
            class="flex max-w-md items-start gap-3 rounded-sm border border-[#009060]/20 bg-[#e8f8f1] px-5 py-4 text-sm leading-7 text-[#075f43] dark:border-[#e0a800]/20 dark:bg-[#e0a800]/10 dark:text-[#f3dda0]">
            <x-hugeicon name="checkmark-circle-02" class="mt-1 shrink-0 text-xl" />
            <span><strong>تم تسجيل اهتمامك.</strong> سنتواصل معك عند توفر تفاصيل التسجيل.</span>
        </div>
    @elseif (!$event->canExpressInterest())
        <span
            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#123329]/12 bg-white/55 px-5 py-3 text-sm font-bold text-[#123329]/58 dark:border-white/10 dark:bg-white/5 dark:text-[#f7f1df]/58">
            <x-hugeicon name="clock-01" class="text-lg" />
            استقبال الاهتمام غير متاح حاليًا
        </span>
    @elseif (auth('customer')->check())
        <form method="POST" action="{{ route('events.interests.store', $event) }}" class="inline-flex">
            @csrf
            <button type="submit" class="{{ $buttonClass }} whitespace-nowrap overflow-visible shrink-0 min-w-max">
                <span>أبدِ اهتمامك</span>
                <x-hugeicon name="user-heart-02" class="text-lg" />
            </button>
        </form>
    @else
        <details class="group relative overflow-visible">
            <summary
                class="{{ $buttonClass }} whitespace-nowrap overflow-visible shrink-0 min-w-max cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                <span>أبدِ اهتمامك</span>
                <x-hugeicon name="user-heart-02" class="text-lg" />
            </summary>

            <div
                class="absolute start-0 z-30 mt-3 w-[min(22rem,calc(100vw-2rem))] rounded-sm border border-[#009060]/18 bg-white p-5 text-start shadow-2xl shadow-[#123329]/16 dark:border-white/12 dark:bg-[#10231e] dark:shadow-black/40">
                <p class="font-heading text-lg font-bold text-[#123329] dark:text-[#f7f1df]">يلزمك حساب في بيرحاء</p>
                <p class="mt-2 text-sm leading-7 text-[#123329]/68 dark:text-[#f7f1df]/68">أنشئ حسابًا أو سجّل الدخول
                    أولًا، ثم أرسل اهتمامك بهذه الفعالية مباشرة من هذه الصفحة.</p>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('register') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-sm bg-[#009060] px-4 text-sm font-bold text-white transition hover:bg-[#007a52] dark:bg-[#e0a800] dark:text-[#07120f]">إنشاء
                        حساب</a>
                    <a href="{{ route('login') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-sm border border-[#009060]/20 px-4 text-sm font-bold text-[#009060] transition hover:bg-[#009060]/8 dark:border-[#e0a800]/24 dark:text-[#e0a800]">تسجيل
                        الدخول</a>
                </div>
            </div>
        </details>
    @endif
</div>
