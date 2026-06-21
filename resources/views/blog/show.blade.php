@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-4xl">
        <div class="space-y-4">
            @if ($post->category)
                <a href="{{ route('blog.category', $post->category) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-100">{{ $post->category->name }}</a>
            @endif

            <h1 class="text-3xl font-semibold text-emerald-950 dark:text-white md:text-4xl">{{ $post->title }}</h1>

            <div class="text-sm text-emerald-950/60 dark:text-emerald-50/60">
                {{ __('ui.blog.published_at', ['date' => $post->published_at?->format('Y-m-d')]) }}
            </div>

            @if ($post->excerpt)
                <p class="text-lg leading-8 text-emerald-950/70 dark:text-emerald-50/70">{{ $post->excerpt }}</p>
            @endif
        </div>

        @if ($imageUrl = $post->featuredImageUrl())
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="mt-8 aspect-[16/9] w-full rounded-lg object-cover shadow-sm">
        @endif

        <div class="prose prose-zinc mt-8 max-w-none dark:prose-invert prose-img:rounded-lg prose-a:text-emerald-700 dark:prose-a:text-emerald-300" dir="rtl">
            {!! $post->renderRichContent('content') !!}
        </div>
    </article>
@endsection
