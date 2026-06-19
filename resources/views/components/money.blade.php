@props([
    'amountBaisa',
    'currency' => 'OMR',
])

@php
    $currencyCode = strtoupper((string) $currency);
    $amount = number_format(((int) $amountBaisa) / 1000, 3);
@endphp

<span {{ $attributes->merge(['dir' => 'ltr', 'class' => 'inline-flex items-baseline gap-1 whitespace-nowrap tabular-nums']) }}>
    @if ($currencyCode === 'OMR')
        <svg
            aria-hidden="true"
            data-omr-symbol
            viewBox="0 0 64 44"
            class="inline-block shrink-0"
            style="width: 1.18em; height: 0.78em; transform: translateY(0.04em); fill: currentColor;"
        >
            <path d="M12 16h20c-1-7 4-13 12-13 5 0 10 2 15 6l-4 10c-4-4-8-6-12-6-4 0-7 2-8 5h26l-5 9H2l5-11h5Z" />
            <path d="M8 29h54l-5 10H0l5-10h3Z" />
            <path d="M31 18c2 6 8 9 17 9h8l-5 8h-5c-13 0-22-6-25-17h10Z" />
        </svg>
        <span class="sr-only">OMR</span>
        <span>{{ $amount }}</span>
    @else
        <span>{{ $amount }}</span>
        <span>{{ $currencyCode }}</span>
    @endif
</span>
