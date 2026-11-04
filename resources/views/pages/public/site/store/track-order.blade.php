@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'robots' => $robots,
])

@section('content')
    <main class="min-h-[70vh] pb-20">
        <section class="relative isolate overflow-hidden bg-[#123329] text-[#f7f1df]">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_15%_15%,rgba(42,163,121,0.24),transparent_36%),radial-gradient(circle_at_85%_80%,rgba(224,168,0,0.13),transparent_32%)]"></div>
            <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.65fr)] lg:items-end lg:gap-20 lg:px-8 lg:py-20">
                <div>
                    <p class="text-sm font-bold tracking-[0.14em] text-[#e0b853]">قهوة بيرحاء / خدمة الطلبات</p>
                    <h1 class="mt-5 max-w-[15ch] font-heading text-4xl font-bold leading-tight sm:text-5xl">طلبك واضح في كل خطوة.</h1>
                    <p class="mt-5 max-w-[55ch] text-base leading-8 text-[#d2e7df]/80">أدخل رقم الطلب ورقم الهاتف المستخدم عند إنشائه، وشاهد حالته الحالية وما تبقى حتى الاستلام.</p>
                </div>
                <div class="flex items-start gap-4 border-s-2 border-[#e0b853] ps-5 text-sm leading-7 text-[#d2e7df]/75">
                    <x-hugeicon name="information-circle" class="mt-1 shrink-0 text-2xl text-[#e0b853]" />
                    <p>لخصوصيتك، نتحقق من رقم الطلب والهاتف معًا. لن تُعرض تفاصيل الطلب باستخدام الرقم وحده.</p>
                </div>
            </div>
        </section>

        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <section aria-labelledby="tracking-form-title" class="relative -mt-7 rounded-sm border border-[#2a8069]/14 bg-white p-5 shadow-[0_24px_70px_rgba(18,51,41,0.08)] sm:p-8 dark:border-white/10 dark:bg-[#10251e]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold tracking-[0.16em] text-[#a97611]">01 / البحث عن الطلب</p>
                        <h2 id="tracking-form-title" class="mt-2 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">تتبع طلبك</h2>
                    </div>
                    @if (auth('customer')->check())
                        <a href="{{ route('customer.store.orders.index') }}" class="text-sm font-bold text-[#007a52] underline-offset-4 hover:underline dark:text-[#6ee7b7]">طلباتي في حسابي</a>
                    @elseif (auth('minor-profile')->check())
                        <a href="{{ route('minor.orders.index') }}" class="text-sm font-bold text-[#007a52] underline-offset-4 hover:underline dark:text-[#6ee7b7]">طلباتي في حسابي</a>
                    @endif
                </div>

                @error('lookup')
                    <div role="alert" class="mt-6 rounded-sm border border-red-300/70 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 dark:border-red-500/30 dark:bg-red-950/30 dark:text-red-200">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('store.orders.track.lookup') }}" class="mt-7 grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                    @csrf
                    <div>
                        <label for="tracking-reference" class="mb-2 block text-sm font-bold text-[#123329] dark:text-[#f7f1df]">رقم الطلب</label>
                        <input id="tracking-reference" name="reference" type="text" dir="ltr" value="{{ old('reference', $order?->reference) }}" autocomplete="off" required maxlength="32" placeholder="10000001" class="min-h-12 w-full rounded-sm border border-[#2a8069]/25 bg-[#f8fbf9] px-4 text-start text-sm text-[#123329] outline-none transition focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/15 dark:bg-white/5 dark:text-[#f7f1df]">
                        @error('reference') <p class="mt-2 text-xs font-semibold text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tracking-phone" class="mb-2 block text-sm font-bold text-[#123329] dark:text-[#f7f1df]">رقم الهاتف المرتبط بالطلب</label>
                        <input id="tracking-phone" name="phone" type="tel" dir="ltr" inputmode="tel" autocomplete="tel" required maxlength="32" placeholder="+968 9XXXXXXX" class="min-h-12 w-full rounded-sm border border-[#2a8069]/25 bg-[#f8fbf9] px-4 text-start text-sm text-[#123329] outline-none transition focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/15 dark:bg-white/5 dark:text-[#f7f1df]">
                        @error('phone') <p class="mt-2 text-xs font-semibold text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#007a52] px-7 py-3 text-sm font-bold text-white transition hover:bg-[#006746] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#007a52]">
                        عرض الحالة
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </button>
                </form>
            </section>

            @if ($order && $progress)
                <section aria-labelledby="tracking-result-title" class="mt-12">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold tracking-[0.16em] text-[#a97611]">02 / حالة الطلب</p>
                            <h2 id="tracking-result-title" class="mt-2 font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">رحلة طلبك</h2>
                        </div>
                        <p class="text-sm text-[#315e52]/75 dark:text-[#d2e7df]/65">رقم الطلب <span dir="ltr" class="font-bold text-[#123329] dark:text-[#f7f1df]">{{ $order->reference }}</span></p>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.7fr)_minmax(260px,0.7fr)]">
                        <div class="rounded-sm border border-[#2a8069]/14 bg-white p-5 sm:p-8 dark:border-white/10 dark:bg-[#10251e]">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <h3 class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">تقدّم الطلب</h3>
                                <span class="text-xs font-semibold text-[#315e52]/70 dark:text-[#d2e7df]/65">آخر تحديث: {{ $order->updated_at?->format('Y-m-d H:i') }}</span>
                            </div>
                            <progress value="{{ $progress['completed_segments'] }}" max="{{ $progress['total_segments'] }}" aria-label="تقدّم الطلب" class="mt-6 h-3 w-full overflow-hidden rounded-full accent-[#007a52] [&::-webkit-progress-bar]:bg-[#deeee7] [&::-webkit-progress-value]:bg-[#007a52] [&::-moz-progress-bar]:bg-[#007a52]"></progress>
                            <ol class="mt-7 grid gap-3 {{ count($progress['steps']) === 6 ? 'sm:grid-cols-3 xl:grid-cols-6' : 'sm:grid-cols-3 xl:grid-cols-5' }}">
                                @foreach ($progress['steps'] as $step)
                                    <li class="flex items-center gap-3 rounded-sm border px-3 py-3 {{ $step['current'] ? 'border-[#007a52] bg-[#e8f7ef] text-[#006746] dark:border-[#6ee7b7] dark:bg-[#0f3b2b] dark:text-[#a7f3d0]' : ($step['reached'] ? 'border-[#2a8069]/20 bg-[#f4faf6] text-[#123329] dark:border-white/15 dark:bg-white/5 dark:text-[#f7f1df]' : 'border-[#2a8069]/10 bg-[#f8fbf9] text-[#315e52]/55 dark:border-white/10 dark:bg-white/[0.03] dark:text-[#d2e7df]/50') }}">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $step['reached'] ? 'bg-[#007a52] text-white' : 'bg-[#e2ebe6] text-[#668477] dark:bg-white/10 dark:text-[#d2e7df]/60' }}"><x-hugeicon :name="$step['icon']" class="text-xl" /></span>
                                        <span class="text-xs font-bold leading-5">{{ $step['label'] }}</span>
                                    </li>
                                @endforeach
                            </ol>
                            <p class="mt-6 border-t border-[#2a8069]/12 pt-5 text-sm font-semibold leading-7 text-[#315e52] dark:border-white/10 dark:text-[#d2e7df]/80">{{ $progress['remaining'] }}</p>
                        </div>

                        <aside class="rounded-sm border p-6 {{ $progress['is_exception'] ? 'border-[#d69a4b]/35 bg-[#fff9ed] dark:border-[#e0b853]/25 dark:bg-[#352819]' : 'border-[#2a8069]/18 bg-[#eaf7f0] dark:border-[#6ee7b7]/20 dark:bg-[#123b2d]' }}">
                            <p class="text-xs font-bold tracking-[0.14em] text-[#a97611] dark:text-[#e0b853]">الحالة الحالية</p>
                            <div class="mt-5 flex items-center gap-3">
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-full {{ $progress['is_exception'] ? 'bg-[#f4dfbb] text-[#8a5b12] dark:bg-[#704a1f] dark:text-[#fce2ab]' : 'bg-[#007a52] text-white' }}"><x-hugeicon :name="$progress['is_exception'] ? 'information-circle' : 'check-list'" class="text-2xl" /></span>
                                <h3 class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $progress['status_label'] }}</h3>
                            </div>
                            <p class="mt-5 text-sm leading-7 text-[#315e52] dark:text-[#d2e7df]/80">{{ $progress['message'] }}</p>

                            @if ($progress['is_refund'])
                                <div class="mt-6 border-t border-[#a97611]/20 pt-5">
                                    <p class="text-xs font-bold text-[#8a5b12] dark:text-[#e0b853]">مسار الاسترداد</p>
                                    <progress value="{{ $progress['refund_completed'] ? 2 : 1 }}" max="2" aria-label="تقدّم الاسترداد" class="mt-3 h-2 w-full accent-[#a97611]"></progress>
                                    <div class="mt-2 flex justify-between gap-3 text-xs font-semibold text-[#315e52] dark:text-[#d2e7df]/75"><span>بانتظار الاسترداد</span><span>مسترد</span></div>
                                </div>
                            @endif
                        </aside>
                    </div>
                </section>
            @endif

            <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-[#2a8069]/12 pt-7 text-sm dark:border-white/10">
                <p class="text-[#315e52]/75 dark:text-[#d2e7df]/65">تحتاج إلى مساعدة؟ تواصل معنا مع ذكر رقم الطلب.</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 font-bold text-[#007a52] hover:underline dark:text-[#6ee7b7]">تواصل معنا <x-hugeicon name="arrow-left-02" class="text-lg" /></a>
            </div>
        </div>
    </main>
@endsection
