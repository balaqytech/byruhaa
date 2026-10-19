@props(['name', 'url' => null])

<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center overflow-hidden bg-[#dcecc7] text-[#28533c]']) }}>
    @if ($url)
        <img src="{{ $url }}" alt="صورة {{ $name }}" class="size-full object-cover" />
    @else
        <span role="img" aria-label="صورة افتراضية لـ {{ $name }}" class="font-heading font-black">{{ mb_substr($name, 0, 1) }}</span>
    @endif
</span>
