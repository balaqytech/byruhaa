@props([
    'event',
    'number',
])

@php
    $topics = collect($event->card_topics ?? [])
        ->filter(fn (mixed $topic): bool => is_string($topic) && filled($topic))
        ->take(3)
        ->values();
@endphp

<article
    class="public-card group relative z-0 flex min-h-[31rem] flex-col rounded-[1.4rem] border border-[#17365d]/14 bg-white p-5 shadow-[0_18px_45px_rgba(22,38,63,0.06)] transition duration-300 has-[details[open]]:z-50 hover:-translate-y-1 hover:border-[#0e7c7b]/38 hover:shadow-[0_24px_60px_rgba(14,124,123,0.12)] dark:border-white/12 dark:bg-white/[0.045] dark:shadow-black/20 dark:hover:border-[#e0a800]/35"
    data-event-card>
    <div class="flex items-start justify-between gap-4">
        <span class="flex size-10 items-center justify-center rounded-xl border border-[#17365d]/12 font-heading text-sm font-bold text-[#17365d]/48 dark:border-white/12 dark:text-[#f7f1df]/56">
            {{ $number }}
        </span>

        @if ($topics->isNotEmpty())
            <div class="flex flex-wrap justify-end gap-2">
                @foreach ($topics as $topic)
                    <span class="rounded-full border border-[#0e7c7b]/18 bg-[#0e7c7b]/8 px-3 py-1.5 text-xs font-bold text-[#08716f] dark:border-[#e0a800]/20 dark:bg-[#e0a800]/10 dark:text-[#f3dda0]">{{ $topic }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-8 flex flex-1 flex-col">
        <h2 class="font-heading text-3xl font-bold leading-[1.25] text-[#16263f] dark:text-[#f7f1df]">{{ $event->name }}</h2>

        @if ($event->subtitle)
            <p class="mt-3 font-heading text-lg font-bold text-[#0e7c7b] dark:text-[#e8bd42]">{{ $event->subtitle }}</p>
        @endif

        @if ($event->excerpt)
            <p class="mt-6 line-clamp-5 text-base leading-8 text-[#314b67] dark:text-[#f7f1df]/72">{{ $event->excerpt }}</p>
        @endif

        <div class="mt-auto border-t border-dashed border-[#17365d]/16 pt-5 dark:border-white/14">
            @if ($event->schedule_text)
                <p class="flex items-center gap-3 text-sm leading-7 text-[#536b82] dark:text-[#f7f1df]/64">
                    <x-hugeicon name="clock-01" class="shrink-0 text-lg text-[#0e7c7b] dark:text-[#e8bd42]" />
                    <span>{{ $event->schedule_text }}</span>
                </p>
            @endif

            @if ($event->canBook())
                <a href="{{ route('events.show', $event) }}"
                    class="mt-5 inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl bg-[#0e7c7b] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#096b6a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e0a800] focus-visible:ring-offset-2 dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#efbd1c]">
                    <span>احجز الآن</span>
                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                </a>
            @elseif ($event->canExpressInterest())
                <x-public-event-interest-action
                    :event="$event"
                    class="mt-5 w-full"
                    button-class="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl bg-[#0e7c7b] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#096b6a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e0a800] focus-visible:ring-offset-2 dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#efbd1c]" />
            @else
                <a href="{{ route('events.show', $event) }}"
                    class="mt-5 inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl bg-[#0e7c7b] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#096b6a] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e0a800] focus-visible:ring-offset-2 dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#efbd1c]">
                    <span>تعرّف إلى الفعالية</span>
                    <x-hugeicon name="information-circle" class="text-lg" />
                </a>
            @endif
        </div>
    </div>
</article>
