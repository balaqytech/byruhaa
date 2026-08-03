<?php

use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('إعدادات الملف الشخصي')] class extends Component {
    public string $name = '';
    public ?string $email = null;
    public ?string $phone_number = null;
    public ?string $civil_id = null;
    public ?string $address = null;
    public ?string $wilaya = null;
    public ?string $area = null;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone_number = $customer->phone_number;
        $this->civil_id = $customer->civil_id;
        $this->address = $customer->address;
        $this->wilaya = $customer->wilaya;
        $this->area = $customer->area;
    }

    public function updateProfileInformation(PhoneNumberNormalizer $phoneNumberNormalizer): void
    {
        $customer = Auth::guard('customer')->user();
        $this->phone_number = $phoneNumberNormalizer->normalize($this->phone_number);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(Customer::class)->ignore($customer->id)],
            'phone_number' => ['required', 'string', 'phone:OM', Rule::unique(Customer::class)->ignore($customer->id)],
            'civil_id' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'wilaya' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
        ]);

        $customer->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone_number' => $validated['phone_number'],
            'civil_id' => $validated['civil_id'],
            'address' => $validated['address'],
            'wilaya' => $validated['wilaya'],
            'area' => $validated['area'],
        ])->save();

        if ($customer->refresh()->hasCompleteProfile()) {
            $this->dispatch('customer-profile-completed');
        }

        Flux::toast(variant: 'success', text: __('ui.messages.profile_updated'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('ui.profile.settings') }}</flux:heading>

    <x-pages::customer.settings.layout :heading="__('ui.profile.heading')" :subheading="__('ui.profile.subheading')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('ui.fields.name')" type="text" required autofocus autocomplete="name" />
            <flux:input wire:model="email" :label="__('ui.fields.email_address')" type="email" autocomplete="email" />
            <flux:input wire:model="phone_number" :label="__('ui.fields.phone_number')" type="tel" required autocomplete="tel" />
            <flux:input wire:model="civil_id" :label="__('ui.fields.civil_id')" required />
            <flux:input wire:model="address" :label="__('ui.fields.address')" required />

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="wilaya" :label="__('ui.fields.wilaya')" required />
                <flux:input wire:model="area" :label="__('ui.fields.area')" required />
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('ui.actions.save') }}
                </flux:button>
            </div>
        </form>
    </x-pages::customer.settings.layout>
</section>
