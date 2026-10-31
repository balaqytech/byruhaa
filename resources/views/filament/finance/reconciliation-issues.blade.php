<div class="space-y-3 text-sm" dir="rtl">
    @forelse ($issues as $issue)
        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <p class="font-semibold">{{ $issue['source'] }} · {{ $issue['reference'] }} · {{ $issue['severity'] === 'discrepancy' ? 'فرق' : 'معلق' }}</p>
            <p>{{ $issue['message'] }}</p>
            <p>المسؤول: {{ $issue['owner'] }}</p>
        </div>
    @empty
        <p>لا توجد فروق أو عمليات معلقة في هذا الفحص.</p>
    @endforelse
</div>
