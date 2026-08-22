<?php

use App\Actions\CancelEvent;
use App\Actions\ConfirmManualPaymentRefund;
use App\Enums\BookingInstallmentState;
use App\Enums\EventCancellationStatus;
use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Enums\SeatAllocationState;
use App\Jobs\ProcessEventCancellation;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\BookingSeatAllocation;
use App\Modules\Events\Models\Event;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
    ]);
});

test('cancelling an event closes enrollment immediately and queues one reconciliation', function () {
    Queue::fake();
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'enrollment_status' => EventEnrollmentStatus::BookingOpen,
    ]);
    Booking::factory()->count(2)->for($event)->create();

    $first = app(CancelEvent::class)->execute($event, 'تعذر إقامة الفعالية');
    $second = app(CancelEvent::class)->execute($event, 'سبب آخر لا يستبدل السجل');

    expect($event->refresh()->status)->toBe(EventStatus::Cancelled)
        ->and($event->enrollment_status)->toBe(EventEnrollmentStatus::BookingClosed)
        ->and($event->canBook())->toBeFalse()
        ->and($first->id)->toBe($second->id)
        ->and($first->bookings_count)->toBe(2)
        ->and($event->cancellation()->count())->toBe(1);

    Queue::assertPushed(ProcessEventCancellation::class, 1);
});

test('event cancellation refunds captured payments and voids future installments', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => ['refund_id' => 'refund_event_cancelled', 'status' => 'succeeded'],
        ]),
    ]);

    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'enrollment_status' => EventEnrollmentStatus::BookingOpen,
    ]);
    $booking = Booking::factory()->for(Customer::factory())->for($event)->create();
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create();
    $paidInstallment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'sequence' => 1,
        'amount_baisa' => 5000,
        'state' => BookingInstallmentState::Paid,
        'paid_at' => now(),
    ]);
    $futureInstallment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'sequence' => 2,
        'state' => BookingInstallmentState::Pending,
    ]);
    $payment = Payment::factory()->for($paidInstallment, 'bookingInstallment')->create([
        'amount_baisa' => 5000,
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment_event_cancelled',
        'paid_at' => now(),
    ]);
    $allocation = BookingSeatAllocation::factory()->for($booking)->create([
        'event_id' => $event->id,
        'state' => SeatAllocationState::Reserved,
        'reserved_at' => now(),
    ]);

    Queue::fake();
    $cancellation = app(CancelEvent::class)->execute($event, 'إلغاء من المنظم');
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);

    expect($payment->refresh()->state)->toBe(PaymentState::Refunded)
        ->and($futureInstallment->refresh()->state)->toBe(BookingInstallmentState::Voided)
        ->and($booking->refresh()->state->getValue())->toBe('cancelled')
        ->and($booking->cancellation_reason)->toBe('إلغاء من المنظم')
        ->and($cancellation->refresh()->status)->toBe(EventCancellationStatus::Completed)
        ->and($cancellation->refunded_amount_baisa)->toBe(5000)
        ->and($allocation->refresh()->state)->toBe(SeatAllocationState::Released)
        ->and($cancellation->errors)->toBeNull();
});

test('a failed refund leaves the cancellation visible for admin attention and retry', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => false,
            'description' => 'Refund rejected',
        ], 422),
    ]);

    $event = Event::factory()->create();
    $booking = Booking::factory()->for(Customer::factory())->for($event)->create();
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create();
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'amount_baisa' => 3000,
        'state' => BookingInstallmentState::Paid,
    ]);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 3000,
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment_failed_refund',
    ]);
    $allocation = BookingSeatAllocation::factory()->for($booking)->create([
        'event_id' => $event->id,
        'state' => SeatAllocationState::Reserved,
        'reserved_at' => now(),
    ]);

    Queue::fake();
    $cancellation = app(CancelEvent::class)->execute($event, 'إلغاء من المنظم');
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);

    expect($payment->refresh()->state)->toBe(PaymentState::Paid)
        ->and($cancellation->refresh()->status)->toBe(EventCancellationStatus::NeedsAttention)
        ->and($allocation->refresh()->state)->toBe(SeatAllocationState::Reserved)
        ->and($cancellation->errors)->not->toBeEmpty();
});

test('a refund requiring manual processing remains visible for attention without creating duplicates', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => false,
            'code' => 4300,
            'description' => 'Refund is not allowed, please contact thawani support',
        ], 400),
    ]);

    $event = Event::factory()->create();
    $booking = Booking::factory()->for(Customer::factory())->for($event)->create();
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create();
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'amount_baisa' => 3000,
        'state' => BookingInstallmentState::Paid,
    ]);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 3000,
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment_manual_refund',
    ]);

    Queue::fake();
    $cancellation = app(CancelEvent::class)->execute($event, 'Organizer cancellation');
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);

    $refund = PaymentRefund::query()->sole();

    expect($refund->state)->toBe(PaymentRefundState::ManualRequired)
        ->and($payment->refresh()->state)->toBe(PaymentState::Paid)
        ->and($payment->refundableAmountBaisa())->toBe(0)
        ->and($cancellation->refresh()->status)->toBe(EventCancellationStatus::NeedsAttention)
        ->and($cancellation->errors[0])->toHaveKey('payment_refund_id')
        ->and(PaymentRefund::query()->count())->toBe(1);

    app(ConfirmManualPaymentRefund::class)->execute(
        $refund,
        'THW-EVENT-MANUAL-001',
        now(),
        User::factory()->create()->id,
    );
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);

    expect($refund->refresh()->state)->toBe(PaymentRefundState::Succeeded)
        ->and($payment->refresh()->state)->toBe(PaymentState::Refunded)
        ->and($cancellation->refresh()->status)->toBe(EventCancellationStatus::Completed)
        ->and($cancellation->errors)->toBeNull()
        ->and(PaymentRefund::query()->count())->toBe(1);
});
