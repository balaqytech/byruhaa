<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('إعدادات المظهر')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('ui.appearance.settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('ui.appearance.heading')" :subheading="__('ui.appearance.subheading')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light">
                <span class="inline-flex items-center gap-2">
                    <x-hugeicon name="sun-01" class="text-lg" />
                    {{ __('ui.appearance.light') }}
                </span>
            </flux:radio>
            <flux:radio value="dark">
                <span class="inline-flex items-center gap-2">
                    <x-hugeicon name="moon-02" class="text-lg" />
                    {{ __('ui.appearance.dark') }}
                </span>
            </flux:radio>
            <flux:radio value="system">
                <span class="inline-flex items-center gap-2">
                    <x-hugeicon name="computer" class="text-lg" />
                    {{ __('ui.appearance.system') }}
                </span>
            </flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>
</section>
