<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; color: #111827; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin-top: 22px; }
        .meta { border: 1px solid #d1d5db; padding: 10px; margin: 12px 0; }
        .signature { margin-top: 24px; border-top: 1px solid #d1d5db; padding-top: 12px; }
        .signature img { max-height: 90px; max-width: 260px; }
    </style>
</head>
<body>
    <h1>{{ $event->name }} Contract</h1>
    <div class="meta">
        <p><strong>Booking reference:</strong> {{ $booking->reference }}</p>
        <p><strong>Guardian:</strong> {{ $customer->name }}</p>
        <p><strong>Family member:</strong> {{ $familyMember->name }}</p>
        <p><strong>Event date:</strong> {{ $event->starts_at?->format('Y-m-d H:i') ?? 'To be announced' }}</p>
        <p><strong>Location:</strong> {{ $event->location ?: 'To be announced' }}</p>
    </div>

    <h2>Terms</h2>
    {!! $contract->contract_html !!}

    @if ($contract->signature_path)
        <div class="signature">
            <h2>Signature</h2>
            <p><strong>Signed by:</strong> {{ $contract->signed_name }}</p>
            <p><strong>Signed at:</strong> {{ $contract->signed_at?->format('Y-m-d H:i') }}</p>
            <img src="{{ storage_path('app/private/'.$contract->signature_path) }}" alt="Signature">
        </div>
    @endif
</body>
</html>
