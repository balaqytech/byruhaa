<?php

use App\Modules\Identity\Models\FamilyMember;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('العائلة')] class extends Component {
    public ?int $editingFamilyMemberId = null;
    public string $name = '';
    public string $birth_date = '';
    public ?string $school_name = null;
    public ?string $grade = null;
    public ?string $medical_notes = null;
    public ?string $relationship_to_customer = null;

    public function openCreateFamilyMemberModal(): void
    {
        $this->resetFamilyMemberForm();

        Flux::modal('family-member-form')->show();
    }

    public function editFamilyMember(int $familyMemberId): void
    {
        $familyMember = $this->familyMemberQuery()
            ->whereKey($familyMemberId)
            ->firstOrFail();

        $this->editingFamilyMemberId = $familyMember->id;
        $this->name = $familyMember->name;
        $this->birth_date = $familyMember->birth_date->format('Y-m-d');
        $this->school_name = $familyMember->school_name;
        $this->grade = $familyMember->grade;
        $this->medical_notes = $familyMember->medical_notes;
        $this->relationship_to_customer = $familyMember->relationship_to_customer;

        Flux::modal('family-member-form')->show();
    }

    public function saveFamilyMember(): void
    {
        Auth::guard('customer')->user()->ensureProfileIsComplete();

        $validated = $this->validate($this->familyMemberRules());

        if ($this->editingFamilyMemberId) {
            $this->familyMemberQuery()
                ->whereKey($this->editingFamilyMemberId)
                ->firstOrFail()
                ->update($validated);

            Flux::toast(variant: 'success', text: __('ui.messages.family_member_updated'));
        } else {
            Auth::guard('customer')->user()->familyMembers()->create($validated);

            Flux::toast(variant: 'success', text: __('ui.messages.family_member_added'));
        }

        $this->resetFamilyMemberForm();

        Flux::modal('family-member-form')->close();
    }

    public function deleteFamilyMember(int $familyMemberId): void
    {
        Auth::guard('customer')->user()->ensureProfileIsComplete();

        $this->familyMemberQuery()
            ->whereKey($familyMemberId)
            ->delete();

        Flux::toast(variant: 'success', text: __('ui.messages.family_member_deleted'));
    }

    public function with(): array
    {
        return [
            'familyMembers' => $this->familyMemberQuery()
                ->latest()
                ->get(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function familyMemberRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:50'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'relationship_to_customer' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function familyMemberQuery(): Builder
    {
        return FamilyMember::query()
            ->whereBelongsTo(Auth::guard('customer')->user());
    }

    public function resetFamilyMemberForm(): void
    {
        $this->reset('editingFamilyMemberId', 'name', 'birth_date', 'school_name', 'grade', 'medical_notes', 'relationship_to_customer');
        $this->resetValidation();
    }
}; ?>

<section class="flex flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <flux:heading size="xl">{{ __('ui.family.heading') }}</flux:heading>
            <flux:subheading>{{ __('ui.family.subheading') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap gap-3">
            <flux:button wire:click="openCreateFamilyMemberModal" variant="primary">
                <x-hugeicon name="add-01" class="text-lg" />
                {{ __('ui.family.add_family_member') }}
            </flux:button>
            <flux:button :href="route('customer.events.index')" wire:navigate variant="outline">
                <x-hugeicon name="calendar-03" class="text-lg" />
                {{ __('ui.actions.view_events') }}
            </flux:button>
        </div>
    </div>

    <flux:error name="profile" />

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        @if ($familyMembers->isNotEmpty())
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('ui.fields.name') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.family.age') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.family.relationship_to_customer') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.family.school') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.family.grade') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('ui.actions.manage_family') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($familyMembers as $familyMember)
                        <flux:table.row wire:key="family-member-row-{{ $familyMember->id }}">
                            <flux:table.cell variant="strong">{{ $familyMember->name }}</flux:table.cell>
                            <flux:table.cell>{{ $familyMember->birth_date->age }}</flux:table.cell>
                            <flux:table.cell>{{ $familyMember->relationship_to_customer ?: '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $familyMember->school_name ?: __('ui.family.no_school_set') }}</flux:table.cell>
                            <flux:table.cell>{{ $familyMember->grade ?: '-' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-2">
                                    <flux:button wire:click="editFamilyMember({{ $familyMember->id }})" size="sm">
                                        <x-hugeicon name="pencil-edit-02" class="text-base" />
                                        {{ __('ui.actions.edit') }}
                                    </flux:button>
                                    <flux:button wire:click="deleteFamilyMember({{ $familyMember->id }})" variant="danger" size="sm" aria-label="{{ __('ui.actions.delete') }}">
                                        <x-hugeicon name="delete-02" class="text-base" />
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <div class="rounded-xl border border-dashed border-emerald-900/20 p-8 text-center dark:border-white/15">
                <flux:text>{{ __('ui.family.empty') }}</flux:text>
            </div>
        @endif
    </div>

    <flux:modal name="family-member-form" class="w-full max-w-2xl" @close="resetFamilyMemberForm">
        <form wire:submit="saveFamilyMember" class="space-y-5">
            <div>
                <flux:heading>
                    {{ $editingFamilyMemberId ? __('ui.family.edit_family_member') : __('ui.family.add_family_member') }}
                </flux:heading>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="name" :label="__('ui.fields.name')" required />
                <flux:input wire:model="birth_date" :label="__('ui.family.birth_date')" type="date" required />
                <flux:input wire:model="relationship_to_customer" :label="__('ui.family.relationship_to_customer')" />
                <flux:input wire:model="school_name" :label="__('ui.family.school')" />
                <flux:input wire:model="grade" :label="__('ui.family.grade')" />
            </div>

            <flux:textarea wire:model="medical_notes" :label="__('ui.family.medical_notes')" />

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        {{ __('ui.actions.cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    <x-hugeicon :name="$editingFamilyMemberId ? 'checkmark-badge-01' : 'add-01'" class="text-lg" />
                    {{ $editingFamilyMemberId ? __('ui.actions.update') : __('ui.actions.add') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
