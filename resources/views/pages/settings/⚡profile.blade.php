<?php

use App\Models\Customer;
use App\Services\PhoneNumberNormalizer;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('إعدادات الملف الشخصي')] class extends Component {
    public string $name = '';
    public ?string $email = null;
    public ?string $phone_number = null;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone_number = $customer->phone_number;
    }

    public function updateProfileInformation(PhoneNumberNormalizer $phoneNumberNormalizer): void
    {
        $customer = Auth::guard('customer')->user();
        $this->phone_number = $phoneNumberNormalizer->normalize($this->phone_number);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:phone_number', 'string', 'email', 'max:255', Rule::unique(Customer::class)->ignore($customer->id)],
            'phone_number' => ['nullable', 'required_without:email', 'string', 'phone:OM', Rule::unique(Customer::class)->ignore($customer->id)],
        ]);

        $customer->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone_number' => $validated['phone_number'] ?: null,
        ])->save();

        Flux::toast(variant: 'success', text: __('ui.messages.profile_updated'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('ui.profile.settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('ui.profile.heading')" :subheading="__('ui.profile.subheading')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('ui.fields.name')" type="text" required autofocus autocomplete="name" />
            <flux:input wire:model="email" :label="__('ui.fields.email_address')" type="email" autocomplete="email" />
            <flux:input wire:model="phone_number" :label="__('ui.fields.phone_number')" type="tel" autocomplete="tel" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('ui.actions.save') }}
                </flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
