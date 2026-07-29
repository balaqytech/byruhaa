@extends('layouts.public')

@section('content')
    <article class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="grid min-h-[calc(100svh-8rem)] items-center gap-8 py-8 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="public-hero-copy max-w-3xl">
                <a href="{{ route('events.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/18 px-3 py-2 text-sm font-bold text-[#009060] transition hover:border-[#009060]/35 hover:bg-[#009060]/8 dark:border-[#e0a800]/24 dark:text-[#e0a800] dark:hover:bg-[#e0a800]/10">
                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                    <span>الفعاليات</span>
                </a>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-sm bg-[#e01838]/8 px-3 py-1.5 text-xs font-semibold text-[#e01838] dark:bg-[#e01838]/14 dark:text-[#ff7487]">
                        <x-hugeicon name="ticket-01" class="text-base" />
                        {{ $event->type->getLabel() }}
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-sm bg-[#009060]/10 px-3 py-1.5 text-xs font-semibold text-[#009060] dark:bg-[#009060]/18 dark:text-[#78ddb8]">
                        <x-hugeicon name="user-group" class="text-base" />
                        {{ $event->minimum_age }} - {{ $event->maximum_age }}
                    </span>
                </div>

                <h1 class="mt-5 font-heading text-5xl font-bold leading-tight text-[#123329] sm:text-6xl dark:text-[#f7f1df]">
                    {{ $event->name }}
                </h1>

                @if ($event->excerpt)
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-[#123329]/70 dark:text-[#f7f1df]/70">
                        {{ $event->excerpt }}
                    </p>
                @endif

                <div class="mt-8 flex flex-wrap gap-3">
                    @if (Route::has('customer.events.show'))
                        <a href="{{ $bookingUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#009060] px-6 py-3 text-sm font-bold text-white shadow-sm shadow-[#123329]/10 transition hover:bg-[#007a52] dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#f0c63c]">
                            <span>احجز الآن</span>
                            <x-hugeicon name="check-list" class="text-lg" />
                        </a>
                    @endif

                    <a href="#event-details" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#009060]/18 px-6 py-3 text-sm font-bold text-[#009060] transition hover:border-[#009060]/35 hover:bg-[#009060]/8 dark:border-[#e0a800]/24 dark:text-[#e0a800] dark:hover:bg-[#e0a800]/10">
                        <span>استكشف التفاصيل</span>
                        <x-hugeicon name="calendar-03" class="text-lg" />
                    </a>
                </div>
            </div>

            <aside class="public-card border border-[#009060]/12 bg-white/72 p-6 shadow-2xl shadow-[#123329]/10 dark:border-white/10 dark:bg-white/8 dark:shadow-black/30">
                <div class="flex items-center justify-between gap-5 border-b border-[#009060]/12 pb-5 dark:border-white/10">
                    <img src="{{ asset('logo.png') }}" alt="منتجع بيرحاء" class="h-16 w-auto rounded-sm bg-white/70 p-2 dark:bg-white/10">
                    <div class="text-left">
                        <p class="text-xs font-semibold text-[#009060] dark:text-[#e0a800]">BYRUHAA EVENT</p>
                        <p class="mt-1 text-sm text-[#123329]/58 dark:text-[#f7f1df]/58">{{ $event->slug }}</p>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4">
                    <div class="flex items-center justify-between gap-4 rounded-sm bg-[#009060]/8 p-4 dark:bg-white/8">
                        <dt class="flex items-center gap-3 text-sm font-semibold text-[#123329]/70 dark:text-[#f7f1df]/70">
                            <x-hugeicon name="wallet-02" class="text-xl text-[#009060] dark:text-[#e0a800]" />
                            السعر لكل فرد
                        </dt>
                        <dd class="text-xl font-bold text-[#123329] dark:text-[#f7f1df]">
                            <x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" />
                        </dd>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-sm border border-[#009060]/12 bg-white/60 p-4 dark:border-white/10 dark:bg-white/5">
                            <dt class="text-xs font-semibold text-[#123329]/54 dark:text-[#f7f1df]/54">المقاعد المتبقية</dt>
                            <dd class="mt-2 text-2xl font-bold text-[#009060] dark:text-[#e0a800]">{{ $remainingSeats }}</dd>
                        </div>

                        <div class="rounded-sm border border-[#009060]/12 bg-white/60 p-4 dark:border-white/10 dark:bg-white/5">
                            <dt class="text-xs font-semibold text-[#123329]/54 dark:text-[#f7f1df]/54">السعة</dt>
                            <dd class="mt-2 text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $event->seat_capacity }}</dd>
                        </div>
                    </div>

                    <div class="grid gap-3 text-sm text-[#123329]/68 dark:text-[#f7f1df]/68">
                        <div class="flex items-center gap-3">
                            <x-hugeicon name="clock-01" class="text-lg text-[#e0a800]" />
                            <span dir="ltr">{{ $event->starts_at?->format('Y-m-d H:i') ?? __('ui.events.date_to_be_announced') }}</span>
                        </div>

                        @if ($event->ends_at)
                            <div class="flex items-center gap-3">
                                <x-hugeicon name="calendar-03" class="text-lg text-[#1898b0]" />
                                <span dir="ltr">{{ $event->ends_at->format('Y-m-d H:i') }}</span>
                            </div>
                        @endif

                        @if ($event->location)
                            <div class="flex items-center gap-3">
                                <x-hugeicon name="map-pin" class="text-lg text-[#30b070]" />
                                <span>{{ $event->location }}</span>
                            </div>
                        @endif
                    </div>
                </dl>
            </aside>
        </section>

        <section id="event-details" class="grid gap-6 py-10 lg:grid-cols-[1.25fr_0.75fr]">
            <div class="space-y-6">
                <div class="public-card border border-[#009060]/12 bg-white/72 p-6 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-sm bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]">
                            <x-hugeicon name="file-view" class="text-2xl" />
                        </span>
                        <h2 class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">وصف الفعالية</h2>
                    </div>

                    <div class="prose prose-emerald max-w-none prose-headings:font-heading prose-headings:text-[#123329] prose-p:text-[#123329]/72 prose-a:text-[#009060] prose-img:rounded-sm dark:prose-headings:text-[#f7f1df] dark:prose-p:text-[#f7f1df]/72 dark:prose-a:text-[#e0a800]" dir="rtl">
                        {!! $event->description_html ?: '<p>سيتم نشر تفاصيل إضافية لهذه الفعالية قريباً.</p>' !!}
                    </div>
                </div>

                <div class="public-card border border-[#009060]/12 bg-white/72 p-6 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-sm bg-[#1898b0]/10 text-[#1898b0] dark:bg-[#1898b0]/20 dark:text-[#8de7f4]">
                            <x-hugeicon name="payment-02" class="text-2xl" />
                        </span>
                        <h2 class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">خطط الدفع</h2>
                    </div>

                    <div class="mb-4 rounded-sm border border-[#009060]/18 bg-[#ecfff7] p-4 dark:border-[#e0a800]/20 dark:bg-[#009060]/10">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-[#123329] dark:text-[#f7f1df]">{{ __('ui.payments.full_payment') }}</h3>
                                <p class="mt-0.5 text-sm text-[#123329]/62 dark:text-[#f7f1df]/62">{{ __('ui.payments.full_payment_description') }}</p>
                            </div>
                            <x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" class="font-bold text-[#009060] dark:text-[#e0a800]" />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @forelse ($event->paymentPlans as $paymentPlan)
                            @php
                                $installments = $paymentPlan->installments->values();
                                $isValidPlan = $installments->isNotEmpty() && (int) $installments->sum('percentage') === 100;
                                $remainingBaisa = $event->price_baisa;
                            @endphp

                            <section class="rounded-sm border border-[#009060]/12 bg-[#fbf9f1]/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <h3 class="text-lg font-bold text-[#123329] dark:text-[#f7f1df]">{{ $paymentPlan->name }}</h3>

                                @if ($isValidPlan)
                                    <div class="mt-4 grid gap-3">
                                        @foreach ($installments as $installment)
                                            @php
                                                $amountBaisa = $loop->last
                                                    ? $remainingBaisa
                                                    : intdiv($event->price_baisa * $installment->percentage, 100);
                                                $remainingBaisa -= $amountBaisa;
                                            @endphp

                                            <div class="flex items-center justify-between gap-3 rounded-sm bg-white/72 px-3 py-2 text-sm dark:bg-white/8">
                                                <div>
                                                    <p class="font-semibold text-[#123329] dark:text-[#f7f1df]">{{ $installment->name ?: __('ui.payments.installment_number', ['number' => $installment->sequence]) }}</p>
                                                    <p class="mt-0.5 text-xs text-[#123329]/56 dark:text-[#f7f1df]/56">{{ $installment->percentage }}% · <span dir="ltr">{{ $installment->due_date?->format('Y-m-d') }}</span></p>
                                                </div>
                                                <x-money :amount-baisa="$amountBaisa" :currency="$event->currency" class="font-bold text-[#009060] dark:text-[#e0a800]" />
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 text-sm text-[#123329]/62 dark:text-[#f7f1df]/62">{{ __('ui.payments.no_plans') }}</p>
                                @endif
                            </section>
                        @empty
                            <p class="rounded-sm border border-dashed border-[#009060]/18 p-5 text-sm text-[#123329]/62 dark:border-white/10 dark:text-[#f7f1df]/62">{{ __('ui.payments.no_plans') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="public-card border border-[#009060]/12 bg-white/72 p-6 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-sm bg-[#e0a800]/14 text-[#997300] dark:bg-[#e0a800]/18 dark:text-[#e0a800]">
                            <x-hugeicon name="coupon-percent" class="text-2xl" />
                        </span>
                        <h2 class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">الخصومات</h2>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($availableDiscounts as $discount)
                            <section class="rounded-sm border border-[#009060]/12 bg-[#fbf9f1]/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-bold text-[#123329] dark:text-[#f7f1df]">{{ $discount->name }}</h3>
                                    <x-money :amount-baisa="$discount->amount_baisa" :currency="$discount->currency" class="font-bold text-[#009060] dark:text-[#e0a800]" />
                                </div>

                                <p class="mt-3 text-sm text-[#123329]/62 dark:text-[#f7f1df]/62">
                                    @if ($discount->minimum_family_members && $discount->maximum_family_members && $discount->minimum_family_members === $discount->maximum_family_members)
                                        {{ __('ui.events.exact_family_members', ['count' => $discount->minimum_family_members]) }}
                                    @elseif ($discount->minimum_family_members && $discount->maximum_family_members)
                                        {{ __('ui.events.family_member_range', ['min' => $discount->minimum_family_members, 'max' => $discount->maximum_family_members]) }}
                                    @elseif ($discount->minimum_family_members)
                                        {{ __('ui.events.minimum_family_members', ['count' => $discount->minimum_family_members]) }}
                                    @elseif ($discount->maximum_family_members)
                                        {{ __('ui.events.maximum_family_members', ['count' => $discount->maximum_family_members]) }}
                                    @else
                                        {{ __('ui.events.all_family_sizes') }}
                                    @endif
                                </p>
                            </section>
                        @empty
                            <p class="rounded-sm border border-dashed border-[#009060]/18 p-5 text-sm text-[#123329]/62 dark:border-white/10 dark:text-[#f7f1df]/62">
                                لا توجد خصومات نشطة لهذه الفعالية حالياً.
                            </p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </article>
@endsection
