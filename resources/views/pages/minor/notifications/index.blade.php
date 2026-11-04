@extends('layouts.public', ['title' => 'الإشعارات'])

@section('content')
<section class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8" dir="rtl">
    <header class="flex flex-col justify-between gap-5 border-b border-emerald-900/10 pb-7 dark:border-white/10 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('minor.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-300">
                <x-hugeicon name="arrow-left-02" class="rotate-180 text-base" />
                العودة إلى حسابي
            </a>
            <p class="mt-6 text-sm font-medium text-emerald-700 dark:text-emerald-300">حساب {{ $profile->familyMember->name }}</p>
            <h1 class="mt-2 font-heading text-3xl font-semibold text-emerald-950 dark:text-white sm:text-4xl">الإشعارات</h1>
            <p class="mt-3 text-sm leading-7 text-emerald-900/65 dark:text-white/65">تحديثات الطلبات والمحفظة والخدمات المرتبطة بحسابك.</p>
        </div>

        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('minor.notifications.read-all') }}">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-sm border border-emerald-700/20 bg-white px-4 text-sm font-semibold text-emerald-800 transition hover:border-emerald-700/40 hover:bg-emerald-50 active:translate-y-px dark:border-white/15 dark:bg-white/5 dark:text-emerald-200 dark:hover:bg-white/10">
                    <x-hugeicon name="checkmark-circle-01" class="text-lg" />
                    تحديد الكل كمقروء
                </button>
            </form>
        @endif
    </header>

    <x-minor-panel-nav class="mt-7" />

    @if (session('success'))
        <div role="status" class="mt-6 border-s-4 border-emerald-600 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-300/10 dark:text-emerald-100">{{ session('success') }}</div>
    @endif

    <div class="mt-8">
        @forelse ($notifications as $notification)
            @php
                $isUnread = $notification->read_at === null;
                $isWallet = data_get($notification->data, 'type') === 'minor_wallet_top_up';
            @endphp
            <article class="relative grid grid-cols-[auto_1fr] gap-4 border-b border-emerald-900/10 py-5 first:pt-0 dark:border-white/10 sm:gap-5 {{ $isUnread ? 'bg-emerald-50/55 dark:bg-emerald-300/5' : '' }}">
                <span class="mt-1 inline-flex size-11 items-center justify-center rounded-full {{ $isWallet ? 'bg-amber-100 text-amber-800 dark:bg-amber-300/10 dark:text-amber-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-200' }}">
                    <x-hugeicon :name="$isWallet ? 'wallet-02' : 'notification-02'" class="text-xl" />
                </span>

                <div class="min-w-0 pe-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h2 class="font-semibold text-emerald-950 dark:text-white">{{ $isWallet ? 'تحديث المحفظة' : 'تحديث الطلب' }}</h2>
                        <div class="flex items-center gap-2">
                            @if ($isUnread)
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300"><span class="size-2 rounded-full bg-emerald-600"></span>جديد</span>
                            @endif
                            <time datetime="{{ $notification->created_at?->toAtomString() }}" class="text-xs text-emerald-900/50 dark:text-white/50">{{ $notification->created_at?->diffForHumans() }}</time>
                        </div>
                    </div>
                    <p class="mt-2 text-sm leading-7 text-emerald-900/72 dark:text-white/72">{{ data_get($notification->data, 'message', 'لديك تحديث جديد في حسابك.') }}</p>
                    <form method="POST" action="{{ route('minor.notifications.open', $notification->id) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-emerald-700 transition hover:underline active:translate-y-px dark:text-emerald-300">
                            {{ $isWallet ? 'عرض المحفظة' : 'عرض الطلب' }}
                            <x-hugeicon name="arrow-left-02" class="text-base" />
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="grid justify-items-center gap-4 border-y border-dashed border-emerald-900/20 px-5 py-16 text-center dark:border-white/15">
                <span class="inline-flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-200">
                    <x-hugeicon name="notification-02" class="text-2xl" />
                </span>
                <div>
                    <h2 class="font-semibold text-emerald-950 dark:text-white">لا توجد إشعارات بعد</h2>
                    <p class="mt-2 text-sm leading-7 text-emerald-900/60 dark:text-white/60">ستظهر هنا تحديثات طلباتك ومحفظتك عند وصولها.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-7">{{ $notifications->links() }}</div>
</section>
@endsection
