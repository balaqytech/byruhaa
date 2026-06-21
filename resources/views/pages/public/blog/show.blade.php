@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-hero-copy space-y-4">
            @if ($post->category)
                <a href="{{ route('blog.category', $post->category) }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#009060] hover:text-[#123329] dark:text-[#e0a800] dark:hover:text-white">
                    <x-hugeicon name="folder-01" class="text-base" />
                    <span>{{ $post->category->name }}</span>
                </a>
            @endif

            <h1 class="font-heading text-4xl font-bold leading-tight text-[#123329] md:text-5xl dark:text-[#f7f1df]">{{ $post->title }}</h1>

            <div class="inline-flex items-center gap-2 text-sm text-[#123329]/58 dark:text-[#f7f1df]/58">
                <x-hugeicon name="calendar-03" class="text-base text-[#e0a800]" />
                {{ __('ui.blog.published_at', ['date' => $post->published_at?->format('Y-m-d')]) }}
            </div>

            @if ($post->excerpt)
                <p class="text-lg leading-8 text-[#123329]/68 dark:text-[#f7f1df]/68">{{ $post->excerpt }}</p>
            @endif
        </div>

        @if ($imageUrl = $post->featuredImageUrl())
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="public-card mt-8 aspect-[16/9] w-full border border-[#009060]/12 object-cover shadow-2xl shadow-[#123329]/10 dark:border-white/10 dark:shadow-black/30">
        @endif

        <div class="prose prose-emerald prose-lg mt-8 max-w-none prose-headings:font-heading prose-headings:text-[#123329] prose-p:text-[#123329]/72 prose-a:text-[#009060] prose-img:rounded-sm dark:prose-headings:text-[#f7f1df] dark:prose-p:text-[#f7f1df]/72 dark:prose-a:text-[#e0a800]" dir="rtl">
            {!! $post->renderRichContent('content') !!}
        </div>
    </article>
@endsection
