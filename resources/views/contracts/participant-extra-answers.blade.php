@php
    $isPdf = $pdf ?? false;
    $participantExtraAnswers = \App\Support\ParticipantExtraFields::formattedAnswers($contract);
@endphp

@if ($participantExtraAnswers !== [])
    @if ($isPdf)
        <div style="margin-top: 7mm;">
            <h2>{{ __('ui.participant_extra.heading') }}</h2>
            <table class="meta-table">
                @foreach ($participantExtraAnswers as $answer)
                    <tr>
                        <th>{{ $answer['label'] }}</th>
                        <td colspan="3">{!! nl2br(e($answer['value'])) !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @else
        <div class="mt-5 rounded-xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-300/20 dark:bg-sky-300/10">
            <div class="mb-4 flex items-center gap-2 text-sky-800 dark:text-sky-100">
                <x-hugeicon name="check-list" class="text-xl" />
                <flux:heading class="text-base">{{ __('ui.participant_extra.heading') }}</flux:heading>
            </div>

            <dl class="grid gap-3 md:grid-cols-2">
                @foreach ($participantExtraAnswers as $answer)
                    <div class="rounded-lg border border-sky-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                        <dt class="text-xs font-medium text-sky-900/70 dark:text-sky-100/70">{{ $answer['label'] }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-emerald-950 dark:text-emerald-50">{{ $answer['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif
@endif
