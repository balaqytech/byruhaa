<?php

use App\Enums\EventInterestStatus;
use App\Models\EventInterest;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('اهتماماتي')] class extends Component
{
    public function withdraw(int $interestId): void
    {
        $interest = Auth::guard('customer')->user()->eventInterests()->findOrFail($interestId);
        $interest->update(['status' => EventInterestStatus::Withdrawn, 'withdrawn_at' => now()]);
        Flux::toast(variant: 'success', text: 'تم إلغاء الاهتمام.');
    }

    public function with(): array
    {
        return ['interests' => Auth::guard('customer')->user()->eventInterests()->with('event')->latest('last_expressed_at')->get()];
    }
};
?>

<section class="flex flex-col gap-6">
    <div><flux:heading size="xl">اهتماماتي</flux:heading><flux:subheading>الفعاليات التي طلبت معرفة موعد فتح الحجز لها.</flux:subheading></div>
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($interests as $interest)
            <article wire:key="interest-{{ $interest->id }}" class="rounded-2xl border border-emerald-900/10 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                <flux:badge color="{{ $interest->status === EventInterestStatus::Interested ? 'amber' : 'gray' }}">{{ $interest->status->value }}</flux:badge>
                <flux:heading class="mt-4">{{ $interest->event->name }}</flux:heading>
                <flux:text class="mt-2">{{ $interest->event->enrollment_status->getLabel() }}</flux:text>
                <div class="mt-5 flex gap-3">
                    <flux:button :href="route('customer.events.show', $interest->event)" wire:navigate>عرض الفعالية</flux:button>
                    @if ($interest->status === EventInterestStatus::Interested)
                        <flux:button variant="danger" wire:click="withdraw({{ $interest->id }})">إلغاء الاهتمام</flux:button>
                    @endif
                </div>
            </article>
        @empty
            <flux:text>لم تسجل اهتمامًا بأي فعالية بعد.</flux:text>
        @endforelse
    </div>
</section>
