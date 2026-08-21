@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
])

@section('content')
    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
        <header class="public-hero-copy space-y-4">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-[#b07c00]">بِيرُحاء إبراء</p>
            <h1 class="font-heading text-4xl font-bold leading-tight text-[#123329] md:text-5xl dark:text-[#f7f1df]">{{ $page->title }}</h1>
            <div class="flex flex-wrap items-center gap-3 text-sm text-[#315e52]/70 dark:text-[#d2e7df]/65">
                @if ($page->effective_at)
                    <span class="inline-flex items-center gap-2">
                        <x-hugeicon name="calendar-03" class="text-base text-[#e0a800]" />
                        سارية من {{ $page->effective_at->format('Y-m-d') }}
                    </span>
                @endif
                <span>الإصدار {{ $page->version }}</span>
            </div>
        </header>

        <div class="prose prose-emerald prose-lg mt-10 max-w-none prose-headings:font-heading prose-headings:text-[#123329] prose-p:text-[#123329]/72 prose-a:text-[#009060] prose-li:text-[#123329]/72 dark:prose-headings:text-[#f7f1df] dark:prose-p:text-[#f7f1df]/72 dark:prose-a:text-[#e0a800] dark:prose-li:text-[#f7f1df]/72" dir="rtl">
            {!! $page->renderRichContent('content') !!}
        </div>
    </article>
@endsection
