@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="__('ui.brand')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-xl bg-emerald-800 text-amber-300 shadow-sm">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="__('ui.brand')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-xl bg-emerald-800 text-amber-300 shadow-sm">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:brand>
@endif
