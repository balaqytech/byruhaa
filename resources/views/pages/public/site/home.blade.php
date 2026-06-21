@extends('layouts.public')

@section('content')
    <section class="relative isolate overflow-hidden">
        <div class="mx-auto grid min-h-[calc(100svh-5rem)] w-full max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8">
            <div class="public-hero-copy max-w-3xl">
                <div class="mb-8 inline-flex items-center gap-2 border border-[#009060]/16 bg-white/70 px-4 py-2 text-sm font-semibold text-[#009060] shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/10 dark:text-[#e0a800]">
                    <x-hugeicon name="sparkles" class="text-lg" />
                    <span>الموقع قيد التجهيز</span>
                </div>

                <h1 class="font-heading text-5xl font-bold leading-[1.18] text-[#123329] sm:text-6xl lg:text-7xl dark:text-[#f7f1df]">
                    منتجع بيرحاء
                </h1>

                <p class="mt-6 max-w-2xl text-xl leading-9 text-[#123329]/72 sm:text-2xl dark:text-[#f7f1df]/72">
                    نصنع تجارب سياحية وتعليمية بروح عُمانية فاخرة
                </p>

                <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('events.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#009060] px-6 py-3 text-sm font-bold text-white shadow-sm shadow-[#123329]/10 transition hover:bg-[#007a52] dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#f0c63c]">
                        <x-hugeicon name="calendar-03" class="text-xl" />
                        <span>استكشف الفعاليات</span>
                    </a>
                    <a href="{{ route('blog.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#009060]/18 bg-white/56 px-6 py-3 text-sm font-bold text-[#123329] transition hover:border-[#009060]/35 hover:bg-white dark:border-white/10 dark:bg-white/8 dark:text-[#f7f1df] dark:hover:border-[#e0a800]/35 dark:hover:bg-white/12">
                        <x-hugeicon name="book-open-text" class="text-xl" />
                        <span>اقرأ المدونة</span>
                    </a>
                </div>
            </div>

            <div class="public-hero-copy relative min-h-[24rem] overflow-hidden border border-[#009060]/12 bg-white/62 p-5 shadow-2xl shadow-[#123329]/10 lg:min-h-[34rem] dark:border-white/10 dark:bg-white/8 dark:shadow-black/30">
                <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(224,24,56,0.07),transparent_34%),linear-gradient(35deg,rgba(24,152,176,0.14),rgba(0,144,96,0.08)),linear-gradient(215deg,rgba(224,168,0,0.11),transparent_42%)] dark:bg-[linear-gradient(145deg,rgba(224,24,56,0.14),transparent_34%),linear-gradient(35deg,rgba(0,144,96,0.18),rgba(7,18,15,0.18)),linear-gradient(215deg,rgba(224,168,0,0.18),transparent_42%)]"></div>
                <div class="relative flex h-full min-h-[22rem] flex-col justify-between border border-[#009060]/12 p-6 lg:min-h-[32rem] dark:border-white/10">
                    <div class="flex justify-between gap-6 text-sm text-[#123329]/58 dark:text-[#f7f1df]/56">
                        <span>BYRUHAA</span>
                        <span>OMAN</span>
                    </div>

                    <div>
                        <div class="mb-5 h-px w-24 bg-[#e0a800]"></div>
                        <p class="max-w-sm text-2xl leading-10 text-[#123329] dark:text-[#f7f1df]">
                            مساحة هادئة لتجارب راقية، ومواسم تنمو ببطء وأناقة.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
