<?php

use App\Models\FamilyMember;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('العائلة')] class extends Component {
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

        Flux::toast(variant: 'success', text: __('ui.messages.family_member_added'));
    }

    public function deleteFamilyMember(int $familyMemberId): void
    {
        FamilyMember::query()
            ->whereBelongsTo(Auth::guard('customer')->user())
            ->whereKey($familyMemberId)
            ->delete();

        Flux::toast(variant: 'success', text: __('ui.messages.family_member_deleted'));
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

<section class="flex flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <flux:heading size="xl">{{ __('ui.family.heading') }}</flux:heading>
            <flux:subheading>{{ __('ui.family.subheading') }}</flux:subheading>
        </div>

        <flux:button :href="route('events.index')" wire:navigate icon="calendar-days" variant="outline">
            {{ __('ui.actions.view_events') }}
        </flux:button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <form wire:submit="addFamilyMember" class="space-y-4">
                <flux:heading>{{ __('ui.family.add_family_member') }}</flux:heading>
                <flux:input wire:model="name" :label="__('ui.fields.name')" required />
                <flux:input wire:model="birth_date" :label="__('ui.family.birth_date')" type="date" required />
                <flux:input wire:model="school_name" :label="__('ui.family.school')" />
                <flux:input wire:model="grade" :label="__('ui.family.grade')" />
                <flux:input wire:model="emergency_contact_name" :label="__('ui.family.emergency_contact')" />
                <flux:input wire:model="emergency_contact_phone" :label="__('ui.family.emergency_phone')" />
                <flux:textarea wire:model="medical_notes" :label="__('ui.family.medical_notes')" />
                <flux:button variant="primary" type="submit" icon="plus">
                    {{ __('ui.actions.add') }}
                </flux:button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse ($familyMembers as $familyMember)
                <div wire:key="family-member-{{ $familyMember->id }}" class="rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading>{{ $familyMember->name }}</flux:heading>
                            <flux:text>{{ __('ui.family.age') }} {{ $familyMember->birth_date->age }} · {{ $familyMember->school_name ?: __('ui.family.no_school_set') }}</flux:text>
                        </div>
                        <flux:button wire:click="deleteFamilyMember({{ $familyMember->id }})" variant="danger" icon="trash" size="sm" />
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-8 text-center dark:border-white/15 dark:bg-white/5">
                    <flux:text>{{ __('ui.family.empty') }}</flux:text>
                </div>
            @endforelse
        </div>
    </div>
</section>
