@extends('layouts.public')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-hero-copy max-w-3xl">
            <p class="inline-flex items-center gap-2 text-sm font-semibold text-[#009060] dark:text-[#e0a800]">
                <x-hugeicon name="calendar-03" class="text-lg" />
                <span>الفعاليات</span>
            </p>
            <h1 class="mt-4 font-heading text-4xl font-bold text-[#123329] sm:text-5xl dark:text-[#f7f1df]">تجارب مختارة
                بعناية</h1>
            <p class="mt-5 text-lg leading-8 text-[#123329]/68 dark:text-[#f7f1df]/68">
                رحلات ومخيمات ومواسم تعليمية مصممة بإيقاع هادئ وتفاصيل تليق بضيوف بيرحاء.
            </p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($events as $event)
                <x-public-event-card :event="$event" :number="$loop->iteration" />
            @empty
                <div
                    class="public-card border border-dashed border-[#009060]/24 bg-white/66 px-6 py-16 text-center md:col-span-2 xl:col-span-3 dark:border-[#e0a800]/24 dark:bg-white/8">
                    <x-hugeicon name="calendar-remove-01" class="mx-auto text-4xl text-[#e0a800]" />
                    <h2 class="mt-5 font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">لا توجد فعاليات
                        منشورة حالياً</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[#123329]/64 dark:text-[#f7f1df]/64">
                        نعمل على تجهيز برنامج يليق بتجربة بيرحاء. ستظهر الفعاليات هنا فور فتح باب الحجز.
                    </p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
