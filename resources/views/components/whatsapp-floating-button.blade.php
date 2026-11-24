@props(['whatsappUrl', 'alwaysVisible' => false])

<a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" data-whatsapp-floating-button
        class="{{ $alwaysVisible ? '' : 'pointer-events-none invisible translate-y-3 opacity-0' }} fixed bottom-24 end-4 z-[60] inline-flex max-w-[calc(100vw-2rem)] items-center gap-3 rounded-sm bg-[#25d366] px-4 py-3 text-white shadow-2xl shadow-[#123329]/24 ring-1 ring-white/28 transition duration-300 hover:-translate-y-0.5 hover:bg-[#1fbd59] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#dfb458] motion-reduce:transition-none lg:bottom-6 lg:end-6"
        title="تحتاج مساعدة؟ تحدث مع مساعدنا الذكي" aria-label="تحتاج مساعدة؟ تحدث مع مساعدنا الذكي">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-sm bg-white/16">
            <svg class="size-6" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                <path
                    d="M12.04 2a9.87 9.87 0 0 0-8.49 14.92L2.2 22l5.22-1.33A9.86 9.86 0 1 0 12.04 2Zm0 1.82a8.04 8.04 0 1 1-4.1 14.96l-.31-.18-3.03.77.79-2.95-.2-.32a8.04 8.04 0 0 1 6.85-12.28Zm-3.42 3.9c-.18 0-.46.07-.7.33-.24.27-.92.9-.92 2.2 0 1.29.94 2.54 1.07 2.72.13.18 1.82 2.91 4.49 3.96 2.22.87 2.67.7 3.15.66.48-.04 1.55-.63 1.77-1.24.22-.61.22-1.13.15-1.24-.06-.11-.24-.18-.5-.31-.26-.13-1.55-.77-1.79-.85-.24-.09-.41-.13-.59.13-.18.26-.68.85-.83 1.02-.15.18-.31.2-.57.07-.26-.13-1.1-.41-2.1-1.3-.78-.69-1.3-1.55-1.45-1.81-.15-.26-.02-.4.12-.53.12-.12.26-.31.39-.46.13-.15.18-.26.26-.44.09-.18.04-.33-.02-.46-.07-.13-.59-1.42-.81-1.95-.21-.51-.43-.44-.59-.45h-.5Z" />
            </svg>
        </span>
        <span class="grid min-w-0 text-start leading-tight">
            <span class="text-sm font-bold">تحتاج مساعدة؟</span>
            <span class="text-xs text-white/86">تحدث مع مساعدنا الذكي</span>
        </span>
    </a>
