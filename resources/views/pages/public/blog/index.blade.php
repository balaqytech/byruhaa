@extends('layouts.public')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-hero-copy flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div class="max-w-3xl">
                <p class="inline-flex items-center gap-2 text-sm font-semibold text-[#009060] dark:text-[#e0a800]">
                    <x-hugeicon name="book-open-text" class="text-lg" />
                    <span>{{ __('ui.blog.heading') }}</span>
                </p>
                <h1 class="mt-4 font-heading text-4xl font-bold text-[#123329] sm:text-5xl dark:text-[#f7f1df]">{{ $currentCategory->name ?? __('ui.blog.all_posts') }}</h1>
                <p class="mt-5 text-lg leading-8 text-[#123329]/68 dark:text-[#f7f1df]/68">
                    {{ $currentCategory->description ?? __('ui.blog.subheading') }}
                </p>
            </div>
        </div>

        <div class="mt-12 grid gap-8 lg:grid-cols-[16rem_1fr]">
            <aside class="h-fit border border-[#009060]/12 bg-white/66 p-4 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <h2 class="text-sm font-semibold text-[#123329] dark:text-[#f7f1df]">{{ __('ui.blog.categories') }}</h2>
                <nav class="mt-3 grid gap-1 text-sm">
                    <a href="{{ route('blog.index') }}" class="rounded-sm px-3 py-2 transition {{ isset($currentCategory) ? 'text-[#123329]/66 hover:bg-[#009060]/8 hover:text-[#123329] dark:text-[#f7f1df]/66 dark:hover:bg-white/10 dark:hover:text-white' : 'bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]' }}">
                        {{ __('ui.blog.all_posts') }}
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ route('blog.category', $category) }}" class="flex items-center justify-between gap-3 rounded-sm px-3 py-2 transition {{ isset($currentCategory) && $currentCategory->is($category) ? 'bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]' : 'text-[#123329]/66 hover:bg-[#009060]/8 hover:text-[#123329] dark:text-[#f7f1df]/66 dark:hover:bg-white/10 dark:hover:text-white' }}">
                            <span>{{ $category->name }}</span>
                            <span class="text-xs">{{ $category->posts_count }}</span>
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($posts as $post)
                    <article class="public-card group flex min-h-96 flex-col overflow-hidden border border-[#009060]/12 bg-white/66 shadow-sm shadow-[#123329]/5 transition duration-300 hover:-translate-y-1 hover:border-[#009060]/26 hover:bg-white dark:border-white/10 dark:bg-white/8 dark:shadow-black/20 dark:hover:border-[#e0a800]/28 dark:hover:bg-white/12">
                        <div class="aspect-[16/9] w-full overflow-hidden bg-[#eef8f3] dark:bg-white/8">
                            @if ($imageUrl = $post->featuredImageUrl())
                                <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-[linear-gradient(135deg,rgba(224,24,56,0.10),rgba(24,152,176,0.14),rgba(48,176,112,0.12))]">
                                    <x-hugeicon name="image-01" class="text-4xl text-[#009060] dark:text-[#e0a800]" />
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col gap-4 p-5">
                            <div class="space-y-2">
                                @if ($post->category)
                                    <a href="{{ route('blog.category', $post->category) }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#009060] hover:text-[#123329] dark:text-[#e0a800] dark:hover:text-white">
                                        <x-hugeicon name="folder-01" class="text-base" />
                                        <span>{{ $post->category->name }}</span>
                                    </a>
                                @endif
                                <h2 class="font-heading text-2xl font-bold leading-tight text-[#123329] dark:text-[#f7f1df]">
                                    <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                                </h2>
                                @if ($post->excerpt)
                                    <p class="line-clamp-3 text-sm leading-7 text-[#123329]/64 dark:text-[#f7f1df]/64">{{ $post->excerpt }}</p>
                                @endif
                            </div>

                            <div class="mt-auto flex items-center justify-between gap-4 text-sm text-[#123329]/58 dark:text-[#f7f1df]/58">
                                <span class="inline-flex items-center gap-2">
                                    <x-hugeicon name="calendar-03" class="text-base text-[#e0a800]" />
                                    {{ __('ui.blog.published_at', ['date' => $post->published_at?->format('Y-m-d')]) }}
                                </span>
                                <a href="{{ route('blog.show', $post) }}" class="font-bold text-[#009060] hover:text-[#123329] dark:text-[#e0a800] dark:hover:text-white">{{ __('ui.blog.read_more') }}</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="public-card border border-dashed border-[#009060]/24 bg-white/66 px-6 py-16 text-center md:col-span-2 xl:col-span-3 dark:border-[#e0a800]/24 dark:bg-white/8">
                        <x-hugeicon name="book-open-text" class="mx-auto text-4xl text-[#e0a800]" />
                        <p class="mt-5 text-[#123329]/68 dark:text-[#f7f1df]/68">{{ __('ui.blog.empty') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="mt-10 text-[#123329] dark:text-[#f7f1df]">
            {{ $posts->links() }}
        </div>
    </section>
@endsection
