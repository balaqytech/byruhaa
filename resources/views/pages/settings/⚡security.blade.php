<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        $customer = Auth::guard('customer')->user();

        $validated = $this->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $customer->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Security settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Security')" :subheading="__('Update your account password')">
        <form wire:submit="updatePassword" class="my-6 w-full space-y-6">
            <flux:input wire:model="current_password" :label="__('Current password')" type="password" required autocomplete="current-password" viewable />
            <flux:input wire:model="password" :label="__('New password')" type="password" required autocomplete="new-password" viewable />
            <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />

            <flux:button variant="primary" type="submit" data-test="update-password-button">
                {{ __('Save password') }}
            </flux:button>
        </form>
    </x-pages::settings.layout>
</section>
