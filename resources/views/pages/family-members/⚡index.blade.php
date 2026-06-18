<?php

use App\Models\FamilyMember;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Family')] class extends Component {
    public string $name = '';
    public string $birth_date = '';
    public ?string $school_name = null;
    public ?string $grade = null;
    public ?string $medical_notes = null;
    public ?string $emergency_contact_name = null;
    public ?string $emergency_contact_phone = null;

    public function addFamilyMember(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:50'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:255'],
        ]);

        Auth::guard('customer')->user()->familyMembers()->create($validated);

        $this->reset('name', 'birth_date', 'school_name', 'grade', 'medical_notes', 'emergency_contact_name', 'emergency_contact_phone');

        Flux::toast(variant: 'success', text: __('Family member added.'));
    }

    public function deleteFamilyMember(int $familyMemberId): void
    {
        FamilyMember::query()
            ->whereBelongsTo(Auth::guard('customer')->user())
            ->whereKey($familyMemberId)
            ->delete();

        Flux::toast(variant: 'success', text: __('Family member deleted.'));
    }

    public function with(): array
    {
        return [
            'familyMembers' => Auth::guard('customer')->user()
                ->familyMembers()
                ->latest()
                ->get(),
        ];
    }
}; ?>

<section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Family') }}</flux:heading>
        <flux:subheading>{{ __('Create reusable family profiles for event bookings.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <flux:card>
            <form wire:submit="addFamilyMember" class="space-y-4">
                <flux:heading>{{ __('Add family member') }}</flux:heading>
                <flux:input wire:model="name" :label="__('Name')" required />
                <flux:input wire:model="birth_date" :label="__('Birth date')" type="date" required />
                <flux:input wire:model="school_name" :label="__('School')" />
                <flux:input wire:model="grade" :label="__('Grade')" />
                <flux:input wire:model="emergency_contact_name" :label="__('Emergency contact')" />
                <flux:input wire:model="emergency_contact_phone" :label="__('Emergency phone')" />
                <flux:textarea wire:model="medical_notes" :label="__('Medical notes')" />
                <flux:button variant="primary" type="submit" icon="plus">
                    {{ __('Add') }}
                </flux:button>
            </form>
        </flux:card>

        <div class="space-y-3">
            @forelse ($familyMembers as $familyMember)
                <flux:card wire:key="family-member-{{ $familyMember->id }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading>{{ $familyMember->name }}</flux:heading>
                            <flux:text>{{ __('Age') }} {{ $familyMember->birth_date->age }} · {{ $familyMember->school_name ?: __('No school set') }}</flux:text>
                        </div>
                        <flux:button wire:click="deleteFamilyMember({{ $familyMember->id }})" variant="danger" icon="trash" size="sm" />
                    </div>
                </flux:card>
            @empty
                <flux:card>
                    <flux:text>{{ __('No family members yet.') }}</flux:text>
                </flux:card>
            @endforelse
        </div>
    </div>
</section>
