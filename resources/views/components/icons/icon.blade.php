@props(['name'])

<svg
    {{ $attributes->class('inline-block size-[1em] shrink-0') }}
    data-icon="{{ $name }}"
    aria-hidden="true"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    @switch($name)
        @case('home-01')
            <path d="m3 11 9-7 9 7" />
            <path d="M5 10v10h14V10" />
            <path d="M9 20v-6h6v6" />
            @break

        @case('calendar-03')
        @case('calendar-remove-01')
            <path d="M7 3v3" />
            <path d="M17 3v3" />
            <path d="M4 8h16" />
            <rect width="16" height="17" x="4" y="5" rx="3" />
            @if ($name === 'calendar-remove-01')
                <path d="m9 14 6 4" />
                <path d="m15 14-6 4" />
            @endif
            @break

        @case('book-open-text')
            <path d="M12 6v15" />
            <path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H12v17H7.5A3.5 3.5 0 0 0 4 22Z" />
            <path d="M20 5.5A3.5 3.5 0 0 0 16.5 2H12v17h4.5A3.5 3.5 0 0 1 20 22Z" />
            <path d="M7 8h2" />
            <path d="M15 8h2" />
            <path d="M15 12h2" />
            @break

        @case('information-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5" />
            <path d="M12 8h.01" />
            @break

        @case('mail-01')
            <rect width="18" height="14" x="3" y="5" rx="3" />
            <path d="m4 7 8 6 8-6" />
            @break

        @case('user-circle')
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="10" r="3" />
            <path d="M7 18a5 5 0 0 1 10 0" />
            @break

        @case('sun-01')
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2" />
            <path d="M12 20v2" />
            <path d="m4.93 4.93 1.41 1.41" />
            <path d="m17.66 17.66 1.41 1.41" />
            <path d="M2 12h2" />
            <path d="M20 12h2" />
            <path d="m6.34 17.66-1.41 1.41" />
            <path d="m19.07 4.93-1.41 1.41" />
            @break

        @case('moon-02')
            <path d="M20 15.5A8.5 8.5 0 0 1 8.5 4 7 7 0 1 0 20 15.5Z" />
            @break

        @case('computer')
            <rect width="18" height="12" x="3" y="4" rx="2" />
            <path d="M8 20h8" />
            <path d="M12 16v4" />
            @break

        @case('dashboard-square-01')
            <rect width="18" height="18" x="3" y="3" rx="3" />
            <path d="M8 8h3v3H8z" />
            <path d="M14 8h2" />
            <path d="M14 12h2" />
            <path d="M8 16h8" />
            @break

        @case('login-03')
            <path d="M15 3h3a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-3" />
            <path d="m10 17 5-5-5-5" />
            <path d="M15 12H3" />
            @break

        @case('menu-01')
            <path d="M4 6h16" />
            <path d="M4 12h16" />
            <path d="M4 18h16" />
            @break

        @case('sparkles')
            <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8Z" />
            <path d="m5 16 .8 2.2L8 19l-2.2.8L5 22l-.8-2.2L2 19l2.2-.8Z" />
            @break

        @case('ticket-01')
            <path d="M4 8a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2Z" />
            <path d="M9 6v12" />
            @break

        @case('clock-01')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break

        @case('map-pin')
            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10" r="3" />
            @break

        @case('wallet-02')
            <path d="M4 7a3 3 0 0 1 3-3h11v4H6a2 2 0 0 0 0 4h14v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" />
            <path d="M16 13h4v4h-4a2 2 0 0 1 0-4Z" />
            @break

        @case('arrow-left-02')
            <path d="M19 12H5" />
            <path d="m12 19-7-7 7-7" />
            @break

        @case('image-01')
            <rect width="18" height="16" x="3" y="4" rx="3" />
            <circle cx="8.5" cy="9" r="1.5" />
            <path d="m21 16-5-5L5 20" />
            @break

        @case('folder-01')
            <path d="M3 7a3 3 0 0 1 3-3h4l2 3h6a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3Z" />
            @break

        @case('user-group')
            <circle cx="9" cy="9" r="3" />
            <path d="M3 20a6 6 0 0 1 12 0" />
            <path d="M16 11a3 3 0 0 0 0-6" />
            <path d="M17 20a5 5 0 0 0-3-4.5" />
            @break

        @case('check-list')
            <path d="m4 7 2 2 4-4" />
            <path d="M13 7h7" />
            <path d="m4 16 2 2 4-4" />
            <path d="M13 16h7" />
            @break

        @case('file-view')
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M8 15s1.5-2 4-2 4 2 4 2-1.5 2-4 2-4-2-4-2Z" />
            <circle cx="12" cy="15" r="1" />
            @break

        @case('payment-02')
            <rect width="18" height="14" x="3" y="5" rx="3" />
            <path d="M3 10h18" />
            <path d="M7 15h4" />
            @break

        @case('coupon-percent')
            <path d="M4 8a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2Z" />
            <path d="m9 15 6-6" />
            <path d="M9 9h.01" />
            <path d="M15 15h.01" />
            @break

        @case('contracts')
            <path d="M8 3h8l3 3v15H5V3Z" />
            <path d="M16 3v4h4" />
            <path d="M8 11h8" />
            <path d="M8 15h5" />
            @break

        @case('account-setting-01')
            <circle cx="12" cy="8" r="3" />
            <path d="M5 21a7 7 0 0 1 14 0" />
            <path d="M18 8h3" />
            <path d="M19.5 6.5v3" />
            @break

        @case('logout-01')
            <path d="M9 21H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h3" />
            <path d="M16 17l5-5-5-5" />
            <path d="M21 12H9" />
            @break

        @case('alert-02')
            <path d="M12 3 2 21h20Z" />
            <path d="M12 9v5" />
            <path d="M12 17h.01" />
            @break

        @case('invoice-03')
            <path d="M6 2h12v20l-3-2-3 2-3-2-3 2Z" />
            <path d="M9 8h6" />
            <path d="M9 12h6" />
            <path d="M9 16h4" />
            @break

        @case('refund-02')
            <path d="M9 14 4 9l5-5" />
            <path d="M4 9h10a6 6 0 1 1 0 12h-3" />
            @break

        @case('checkmark-badge-01')
            <path d="M12 2 9.5 4.2 6.2 4 5.7 7.3 3 9.2 4.4 12 3 14.8l2.7 1.9.5 3.3 3.3-.2L12 22l2.5-2.2 3.3.2.5-3.3 2.7-1.9L19.6 12 21 9.2l-2.7-1.9-.5-3.3-3.3.2Z" />
            <path d="m8.5 12 2.2 2.2 4.8-5" />
            @break

        @case('copy-01')
            <rect width="12" height="12" x="8" y="8" rx="2" />
            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
            @break

        @case('qr-code')
            <path d="M4 4h6v6H4z" />
            <path d="M14 4h6v6h-6z" />
            <path d="M4 14h6v6H4z" />
            <path d="M14 14h2" />
            <path d="M18 14h2v2" />
            <path d="M14 18h6" />
            @break

        @case('cancel-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="m15 9-6 6" />
            <path d="m9 9 6 6" />
            @break

        @case('loading-03')
            <path d="M21 12a9 9 0 1 1-6.2-8.56" />
            @break

        @case('checkmark-circle-01')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" />
            @break

        @case('lock-key')
            <rect width="16" height="11" x="4" y="11" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            <path d="M12 15v3" />
            @break

        @case('view')
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
            <circle cx="12" cy="12" r="3" />
            @break

        @case('view-off')
            <path d="m3 3 18 18" />
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
            <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 4.1" />
            <path d="M6.6 6.6A18 18 0 0 0 2 12s3.5 7 10 7c1.8 0 3.4-.5 4.8-1.2" />
            @break

        @case('reload')
            <path d="M21 12a9 9 0 0 1-15.5 6.2" />
            <path d="M3 12A9 9 0 0 1 18.5 5.8" />
            <path d="M18 2v4h4" />
            <path d="M6 22v-4H2" />
            @break

        @case('add-01')
            <path d="M12 5v14" />
            <path d="M5 12h14" />
            @break

        @case('pencil-edit-02')
            <path d="m14 5 5 5" />
            <path d="M4 20h5L19 10a3.5 3.5 0 0 0-5-5L4 15Z" />
            @break

        @case('delete-02')
            <path d="M4 7h16" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
            <path d="M6 7l1 14h10l1-14" />
            <path d="M9 7V4h6v3" />
            @break

        @case('signature')
            <path d="M3 17c4 2 6-2 8-7 1.4-3.4 5-2 4 1-1 4-5 6-3 7 1.5.8 3-2 5-2s2.5 2 4 1" />
            <path d="M3 21h18" />
            @break

        @case('download-01')
            <path d="M12 3v12" />
            <path d="m7 10 5 5 5-5" />
            <path d="M5 21h14" />
            @break

        @case('student')
            <path d="m2 8 10-5 10 5-10 5Z" />
            <path d="M6 10v5c0 2 3 4 6 4s6-2 6-4v-5" />
            @break

        @default
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4" />
            <path d="M12 16h.01" />
    @endswitch
</svg>
