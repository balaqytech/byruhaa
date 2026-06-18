@props([
    'sidebar' => false,
])

<a {{ $attributes->merge(['class' => 'inline-flex items-center rounded-xl bg-[#9fd4c2] p-1 shadow-sm ring-1 ring-emerald-900/10']) }}>
    <img
        src="{{ asset('logo.png') }}"
        alt="{{ __('ui.brand') }}"
        class="{{ $sidebar ? 'h-10' : 'h-11' }} w-auto rounded-lg object-contain"
    >
</a>
