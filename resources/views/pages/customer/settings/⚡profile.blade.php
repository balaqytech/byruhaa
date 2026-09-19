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
    public bool $phoneVerified = false;
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
        $this->phoneVerified = $customer->hasVerifiedPhone();
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
            'phone_number' => ['required', 'string', 'phone:INTERNATIONAL,OM', Rule::unique(Customer::class)->ignore($customer->id)],
            'civil_id' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'wilaya' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
        ]);

        $phoneChanged = $customer->phone_number !== $validated['phone_number'];
        $customer->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone_number' => $validated['phone_number'],
            'phone_verified_at' => $phoneChanged ? null : $customer->phone_verified_at,
            'civil_id' => $validated['civil_id'],
            'address' => $validated['address'],
            'wilaya' => $validated['wilaya'],
            'area' => $validated['area'],
        ])->save();
        $this->phoneVerified = ! $phoneChanged && $customer->hasVerifiedPhone();

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

        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-emerald-900/10 p-4 dark:border-white/10">
            @if ($phoneVerified)
                <flux:badge color="emerald">رقم الهاتف موثّق</flux:badge>
            @else
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <form method="POST" action="{{ route('customer.phone-verification.send') }}">
                        @csrf
                        <flux:button type="submit" variant="outline">إرسال رمز التحقق</flux:button>
                    </form>
                    <form method="POST" action="{{ route('customer.phone-verification.verify') }}" class="flex items-end gap-2">
                        @csrf
                        <flux:input name="code" label="رمز التحقق" inputmode="numeric" maxlength="6" />
                        <flux:button type="submit" variant="primary">تحقق</flux:button>
                    </form>
                </div>
            @endif
        </div>
    </x-pages::customer.settings.layout>
</section>
