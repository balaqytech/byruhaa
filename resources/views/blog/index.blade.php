@extends('layouts.public')

@section('content')
    <section class="flex flex-col gap-8">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ __('ui.blog.heading') }}</p>
                <h1 class="mt-2 text-3xl font-semibold text-emerald-950 dark:text-white">{{ $currentCategory->name ?? __('ui.blog.all_posts') }}</h1>
                <p class="mt-3 text-base leading-7 text-emerald-950/70 dark:text-emerald-50/70">
                    {{ $currentCategory->description ?? __('ui.blog.subheading') }}
                </p>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[16rem_1fr]">
            <aside class="h-fit rounded-lg border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                <h2 class="text-sm font-semibold text-emerald-950 dark:text-white">{{ __('ui.blog.categories') }}</h2>
                <nav class="mt-3 grid gap-1 text-sm">
                    <a href="{{ route('blog.index') }}" class="rounded-md px-3 py-2 {{ isset($currentCategory) ? 'text-emerald-950/70 hover:bg-emerald-50 dark:text-emerald-50/70 dark:hover:bg-white/10' : 'bg-emerald-50 text-emerald-900 dark:bg-emerald-400/15 dark:text-emerald-100' }}">
                        {{ __('ui.blog.all_posts') }}
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ route('blog.category', $category) }}" class="flex items-center justify-between gap-3 rounded-md px-3 py-2 {{ isset($currentCategory) && $currentCategory->is($category) ? 'bg-emerald-50 text-emerald-900 dark:bg-emerald-400/15 dark:text-emerald-100' : 'text-emerald-950/70 hover:bg-emerald-50 dark:text-emerald-50/70 dark:hover:bg-white/10' }}">
                            <span>{{ $category->name }}</span>
                            <span class="text-xs">{{ $category->posts_count }}</span>
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($posts as $post)
                    <article class="flex min-h-80 flex-col overflow-hidden rounded-lg border border-emerald-900/10 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-700/30 hover:shadow-md dark:border-white/10 dark:bg-white/5">
                        @if ($imageUrl = $post->featuredImageUrl())
                            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="aspect-[16/9] w-full object-cover">
                        @endif

                        <div class="flex flex-1 flex-col gap-4 p-5">
                            <div class="space-y-2">
                                @if ($post->category)
                                    <a href="{{ route('blog.category', $post->category) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-100">{{ $post->category->name }}</a>
                                @endif
                                <h2 class="text-xl font-semibold text-emerald-950 dark:text-white">
                                    <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                                </h2>
                                @if ($post->excerpt)
                                    <p class="line-clamp-3 text-sm leading-6 text-emerald-950/70 dark:text-emerald-50/70">{{ $post->excerpt }}</p>
                                @endif
                            </div>

                            <div class="mt-auto flex items-center justify-between gap-4 text-sm text-emerald-950/60 dark:text-emerald-50/60">
                                <span>{{ __('ui.blog.published_at', ['date' => $post->published_at?->format('Y-m-d')]) }}</span>
                                <a href="{{ route('blog.show', $post) }}" class="font-medium text-emerald-700 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-100">{{ __('ui.blog.read_more') }}</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-emerald-900/20 bg-white p-8 text-center dark:border-white/15 dark:bg-white/5 md:col-span-2 xl:col-span-3">
                        <p class="text-emerald-950/70 dark:text-emerald-50/70">{{ __('ui.blog.empty') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{ $posts->links() }}
    </section>
@endsection
