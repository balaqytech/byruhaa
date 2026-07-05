<section class="mx-auto flex min-h-[calc(100svh-5rem)] w-full max-w-5xl items-center px-4 py-16 sm:px-6 lg:px-8">
    <div class="public-hero-copy grid w-full gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <div class="relative min-h-72 overflow-hidden rounded-sm border border-[#2a8069]/12 bg-white/70 p-6 shadow-2xl shadow-[#123329]/10 dark:border-white/10 dark:bg-white/8 dark:shadow-black/30">
            <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(42,128,105,0.10),transparent_34%),linear-gradient(35deg,rgba(24,152,176,0.12),rgba(248,232,196,0.22))] dark:bg-[linear-gradient(145deg,rgba(0,144,96,0.16),transparent_34%),linear-gradient(35deg,rgba(24,152,176,0.16),rgba(7,18,15,0.18))]"></div>
            <div class="relative flex min-h-60 flex-col justify-between border border-[#2a8069]/12 p-6 dark:border-white/10">
                <div class="flex items-center justify-between gap-4 text-sm font-semibold text-[#2a8069]/70 dark:text-[#e0a800]">
                    <span>BYRUHAA</span>
                    <span dir="ltr">{{ $code }}</span>
                </div>

                <div>
                    <span class="mb-5 flex size-14 items-center justify-center rounded-sm bg-[#2a8069]/10 text-3xl text-[#2a8069] dark:bg-[#e0a800]/14 dark:text-[#e0a800]">
                        <x-hugeicon :name="$icon" />
                    </span>
                    <p class="font-heading text-7xl font-bold leading-none text-[#123329] dark:text-[#f7f1df]" dir="ltr">{{ $code }}</p>
                </div>
            </div>
        </div>

        <div>
            <p class="inline-flex items-center gap-2 text-sm font-semibold text-[#009060] dark:text-[#e0a800]">
                <x-hugeicon :name="$icon" class="text-lg" />
                <span>{{ __('ui.errors.label') }}</span>
            </p>

            <h1 class="mt-5 font-heading text-4xl font-bold leading-tight text-[#123329] sm:text-6xl dark:text-[#f7f1df]">
                {{ $title }}
            </h1>

            <p class="mt-5 max-w-2xl text-lg leading-8 text-[#123329]/68 dark:text-[#f7f1df]/68">
                {{ $description }}
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('home') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#009060] px-6 py-3 text-sm font-bold text-white shadow-sm shadow-[#123329]/10 transition hover:bg-[#007a52] dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#f0c63c]">
                    <x-hugeicon name="home-01" class="text-xl" />
                    <span>{{ __('ui.errors.home') }}</span>
                </a>

                @if (Route::has('events.index'))
                    <a href="{{ route('events.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#009060]/18 bg-white/56 px-6 py-3 text-sm font-bold text-[#123329] transition hover:border-[#009060]/35 hover:bg-white dark:border-white/10 dark:bg-white/8 dark:text-[#f7f1df] dark:hover:border-[#e0a800]/35 dark:hover:bg-white/12">
                        <x-hugeicon name="calendar-03" class="text-xl" />
                        <span>{{ __('ui.errors.events') }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
