<div wire:poll.10s class="space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold tracking-[0.12em] text-[#718778] dark:text-[#a7b9a9]">مطبخ بِيرُحاء / قائمة العمل</p>
            <h1 class="mt-2 font-heading text-3xl font-black sm:text-4xl">الطلبات أمامك</h1>
            <p class="mt-2 text-sm text-[#687d70] dark:text-[#afc0b3]">تتجدد القائمة تلقائيًا كل ١٠ ثوانٍ.</p>
        </div>
        <div class="flex items-center gap-2 rounded-full border border-[#cbdcc7] bg-[#e8f1df] px-4 py-2 text-xs font-bold text-[#31553f] dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
            <span class="size-2 rounded-full bg-emerald-500"></span>
            الطلبات النشطة: {{ $lanes['new']->count() + $lanes['upcoming']->count() + $lanes['preparing']->count() + $lanes['ready']->count() }}
        </div>
    </div>

    @error('status')
        <p role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-100">{{ $message }}</p>
    @enderror

    <div class="grid items-start gap-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach (['new' => ['title' => 'طلبات جديدة', 'description' => 'يمكن بدء تحضيرها الآن', 'accent' => 'bg-[#e4f0d7] text-[#315a3c] dark:bg-emerald-800/30 dark:text-emerald-200'], 'upcoming' => ['title' => 'طلبات قادمة', 'description' => 'يفتح التحضير قبل الاستلام بـ'.$preparationLeadMinutes.' دقيقة', 'accent' => 'bg-[#edf0f4] text-[#43566e] dark:bg-slate-700/40 dark:text-slate-200'], 'preparing' => ['title' => 'قيد التحضير', 'description' => 'تُحضّر الآن', 'accent' => 'bg-[#fff0d3] text-[#89521b] dark:bg-amber-800/30 dark:text-amber-200'], 'ready' => ['title' => 'جاهزة للاستلام', 'description' => 'يؤكدها مسؤول التسليم', 'accent' => 'bg-[#dcece9] text-[#22645b] dark:bg-teal-800/30 dark:text-teal-200']] as $laneKey => $lane)
            <section class="rounded-2xl border border-[#dce4d9] bg-[#f9faf6] p-4 dark:border-white/10 dark:bg-[#10211c] sm:p-5">
                <div class="mb-5 flex items-center justify-between gap-2">
                    <div><h2 class="font-heading text-xl font-black">{{ $lane['title'] }}</h2><p class="mt-1 text-xs text-[#789083] dark:text-[#a9bdad]">{{ $lane['description'] }}</p></div>
                    <span class="grid size-9 place-items-center rounded-full text-sm font-black {{ $lane['accent'] }}">{{ $lanes[$laneKey]->count() }}</span>
                </div>

                <div class="space-y-3">
                    @forelse ($lanes[$laneKey] as $order)
                        <article wire:key="barista-order-{{ $order->id }}" class="rounded-xl border border-[#e0e6dd] bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#182d25]">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="text-xs font-bold text-[#7a9080] dark:text-[#adc2b3]">طلب</p><h3 class="mt-1 font-mono text-lg font-bold" dir="ltr">{{ $order->reference }}</h3></div>
                                <time class="whitespace-nowrap rounded-lg bg-[#f0f4ec] px-2 py-1 text-xs font-bold text-[#4c6e55] dark:bg-white/10 dark:text-[#c7d9c7]" datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('H:i') }}</time>
                            </div>
                            @if ($order->recipient_name)
                                <p class="mt-3 text-sm font-semibold">{{ $order->recipient_name }}</p>
                            @elseif ($order->customer_name)
                                <p class="mt-3 text-sm font-semibold">{{ $order->customer_name }}</p>
                            @endif
                            <ul class="mt-4 space-y-2 border-t border-[#e9eee7] pt-3 dark:border-white/10">
                                @foreach ($order->items as $item)
                                    <li wire:key="barista-item-{{ $item->id }}" class="flex items-start gap-3 text-sm"><span class="grid size-6 shrink-0 place-items-center rounded-md bg-[#eaf3df] font-bold text-[#2d643f] dark:bg-emerald-800/40 dark:text-emerald-100">{{ $item->quantity }}</span><span>{{ $item->product_name }} @if ($item->option_name) <span class="text-[#718677] dark:text-[#adc2b3]">· {{ $item->option_name }}</span> @endif @if ($item->note) <span class="mt-1 block text-xs text-[#8b6b45] dark:text-amber-200">{{ $item->note }}</span> @endif</span></li>
                                @endforeach
                            </ul>
                            @if ($order->pickup_at)
                                <p class="mt-4 rounded-lg bg-[#f3f6ed] px-3 py-2 text-xs font-semibold text-[#4f6b53] dark:bg-white/5 dark:text-[#bdcfbc]">موعد الاستلام: <time datetime="{{ $order->pickup_at->toIso8601String() }}">{{ $order->pickup_at->format('Y-m-d H:i') }}</time></p>
                            @endif
                            @if ($order->note)
                                <p class="mt-3 text-xs leading-5 text-[#795d42] dark:text-amber-200">ملاحظة: {{ $order->note }}</p>
                            @endif
                            @if ($canPrepare && in_array($laneKey, ['new', 'upcoming'], true))
                                @if ($order->status->getValue() === 'confirmed' && $order->pickup_type === 'scheduled')
                                    <button type="button" wire:click="acceptScheduled({{ $order->id }})" wire:loading.attr="disabled" class="mt-4 w-full rounded-lg border border-[#83ab84] bg-[#e9f3e2] px-3 py-2.5 text-sm font-bold text-[#28563b] transition hover:bg-[#dceccf] disabled:opacity-60 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-100">قبول الطلب المجدول</button>
                                @elseif ($laneKey === 'new')
                                    <button type="button" wire:click="startPreparing({{ $order->id }})" wire:loading.attr="disabled" class="mt-4 w-full rounded-lg bg-[#204d3a] px-3 py-2.5 text-sm font-bold text-white transition hover:bg-[#2c674c] disabled:opacity-60">بدء التحضير</button>
                                @else
                                    <p class="mt-4 text-xs font-semibold text-[#718677] dark:text-[#adc2b3]">{{ $order->preparationOpensAt() ? 'يبدأ التحضير عند '.$order->preparationOpensAt()->format('Y-m-d H:i') : 'موعد الاستلام غير محدد؛ راجع الإدارة.' }}</p>
                                @endif
                            @elseif ($canPrepare && $laneKey === 'preparing')
                                <button type="button" wire:click="markReady({{ $order->id }})" wire:loading.attr="disabled" class="mt-4 w-full rounded-lg bg-[#22645b] px-3 py-2.5 text-sm font-bold text-white transition hover:bg-[#2d7b70] disabled:opacity-60">جاهز للاستلام</button>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-[#d2dfcf] px-4 py-8 text-center text-sm text-[#819486] dark:border-white/15 dark:text-[#a8bbae]">لا توجد طلبات هنا حاليًا.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
