@props(['pages', 'label' => 'سياسات ذات صلة'])

@php
    $policies = $publishedPolicyPages->whereIn('key', $pages);
@endphp

@if ($policies->isNotEmpty())
    <nav {{ $attributes->class(['space-y-2 text-xs leading-6 text-emerald-900/75 dark:text-white/70']) }} aria-label="{{ $label }}" data-policy-links>
        <p>{{ $label }} <span class="text-emerald-900/60 dark:text-white/60">(تفتح في تبويب جديد)</span></p>
        <ul class="flex flex-wrap gap-x-4 gap-y-2">
            @foreach ($policies as $policy)
                <li>
                    <a href="{{ route('policies.show', ['page' => $policy->key]) }}" target="_blank" rel="noopener noreferrer"
                        class="rounded-sm font-semibold text-emerald-700 underline underline-offset-4 hover:text-emerald-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-600 dark:text-emerald-300 dark:hover:text-emerald-100">{{ $policy->title }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
