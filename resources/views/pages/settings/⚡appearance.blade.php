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
            <flux:radio value="light" icon="sun">{{ __('ui.appearance.light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('ui.appearance.dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('ui.appearance.system') }}</flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>
</section>
