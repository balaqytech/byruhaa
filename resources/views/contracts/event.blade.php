<section>
    <h1>{{ $event->name }} Contract</h1>
    <p>Booking reference: {{ $booking->reference }}</p>
    <p>Guardian: {{ $customer->name }}</p>
    <p>Family member: {{ $familyMember->name }}</p>
    <p>Event date: {{ $event->starts_at?->format('Y-m-d H:i') ?? 'To be announced' }}</p>
    <div>{!! $event->contract_terms_html ?: '<p>No additional terms were provided.</p>' !!}</div>
</section>
