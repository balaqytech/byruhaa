@props([
    'state' => null,
    'color' => null,
])

@php
    use App\Enums\BookingInstallmentState;
    use App\Enums\PaymentRefundState;
    use App\Enums\PaymentState;
    use App\Modules\Events\States\Booking\Approved;
    use App\Modules\Events\States\Booking\Cancelled;
    use App\Modules\Events\States\Booking\PendingReview;
    use App\Modules\Events\States\Booking\Rejected;
    use App\Modules\Events\States\Contract\AwaitingSignature;
    use App\Modules\Events\States\Contract\Signed;
    use App\Modules\Events\States\Contract\Voided;

    $badgeColor = $color ?? match (true) {
        $state instanceof Approved,
        $state instanceof Signed,
        $state === BookingInstallmentState::Paid,
        $state === PaymentState::Paid,
        $state === PaymentRefundState::Succeeded => 'green',

        $state === PaymentState::PartiallyRefunded => 'amber',
        $state === PaymentState::Refunded => 'zinc',

        $state instanceof PendingReview,
        $state instanceof AwaitingSignature,
        $state === BookingInstallmentState::Pending,
        $state === PaymentState::Pending,
        $state === PaymentRefundState::Pending => 'sky',

        $state instanceof Rejected,
        $state instanceof Cancelled,
        $state instanceof Voided,
        $state === PaymentState::Failed,
        $state === PaymentState::Cancelled,
        $state === PaymentRefundState::Failed => 'rose',

        default => 'zinc',
    };

    $label = trim($slot->toHtml());

    if ($label === '' && is_object($state) && method_exists($state, 'label')) {
        $label = $state->label();
    }
@endphp

<flux:badge :color="$badgeColor" {{ $attributes->merge(['data-status-color' => $badgeColor]) }}>
    {{ $label }}
</flux:badge>
