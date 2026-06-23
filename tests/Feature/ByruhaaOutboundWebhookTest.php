<?php

use App\Actions\ConfirmThawaniPayment;
use App\Actions\CreateCustomerBooking;
use App\Enums\BookingInstallmentState;
use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingApprovalService;
use App\Services\Webhooks\ByruhaaWebhookSender;
use App\States\Booking\Approved;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;

beforeEach(function (): void {
    config([
        'app.url' => 'https://byruhaa.test',
        'byruhaa.webhooks.booking_created_url' => null,
        'byruhaa.webhooks.booking_approved_url' => null,
        'byruhaa.webhooks.payment_paid_url' => null,
        'byruhaa.webhooks.signing_secret' => null,
        'byruhaa.webhooks.timeout' => 10,
        'byruhaa.webhooks.queue' => 'default',
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
});

test('byruhaa webhook config values exist', function () {
    expect(config('byruhaa.webhooks'))
        ->toHaveKeys([
            'booking_created_url',
            'booking_approved_url',
            'payment_paid_url',
            'signing_secret',
            'timeout',
            'queue',
        ])
        ->and(config('byruhaa.webhooks.timeout'))->toBe(10)
        ->and(config('byruhaa.webhooks.queue'))->toBe('default');
});

test('booking creation queues a webhook with booking event customer and family member payload', function () {
    [$customer, $event, $familyMembers] = byruhaaBookingCreationFixture();

    config([
        'byruhaa.webhooks.booking_created_url' => 'https://partner.test/webhooks/booking-created',
        'byruhaa.webhooks.queue' => 'webhooks',
    ]);

    Queue::fake();

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/booking-created');
    $payload = $job->payload;

    expect($booking->refresh()->booking_created_webhook_sent_at)->not->toBeNull()
        ->and($job->queue)->toBe('webhooks')
        ->and($job->headers)->not->toHaveKey('Signature')
        ->and($payload['event'])->toBe('booking.created')
        ->and($payload['data']['booking']['id'])->toBe($booking->id)
        ->and($payload['data']['booking']['status'])->toBe('pending_review')
        ->and($payload['data']['booking']['customer_panel_url'])->toBe(route('customer.bookings.show', $booking))
        ->and($payload['data']['event']['id'])->toBe($event->id)
        ->and($payload['data']['event']['name'])->toBe('Created Booking Camp')
        ->and($payload['data']['customer']['id'])->toBe($customer->id)
        ->and($payload['data']['customer']['phone'])->toBe('+96892345678')
        ->and($payload['data']['participants'])->toHaveCount(2)
        ->and($payload['data']['participants'][0]['name'])->toBe('First Created Member')
        ->and($payload['data']['participants'][0]['contract_id'])->toBeNull()
        ->and($payload['data']['participants'][0]['contract_status'])->toBeNull()
        ->and($payload['data']['pricing']['unit_price'])->toBe('12.000')
        ->and($payload['data']['pricing']['subtotal'])->toBe('24.000')
        ->and($payload['data']['pricing']['total'])->toBe('24.000');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('booking creation does not queue a webhook when url is empty', function () {
    [$customer, $event, $familyMembers] = byruhaaBookingCreationFixture();

    Queue::fake();

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    Queue::assertNotPushed(CallWebhookJob::class);
    expect($booking->refresh()->booking_created_webhook_sent_at)->toBeNull();
});

test('repeated booking created sender call does not queue duplicate webhooks', function () {
    [$customer, $event, $familyMembers] = byruhaaBookingCreationFixture();

    config([
        'byruhaa.webhooks.booking_created_url' => 'https://partner.test/webhooks/booking-created',
    ]);

    Queue::fake();

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    app(ByruhaaWebhookSender::class)->sendBookingCreated($booking->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
});

test('booking approval queues a signed webhook with booking payload', function () {
    [$booking, $staff] = byruhaaWebhookBookingFixture();

    config([
        'byruhaa.webhooks.booking_approved_url' => 'https://partner.test/webhooks/booking-approved',
        'byruhaa.webhooks.signing_secret' => 'secret',
        'byruhaa.webhooks.queue' => 'webhooks',
    ]);

    Queue::fake();

    $approvedBooking = app(BookingApprovalService::class)->approve($booking, $staff);

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/booking-approved');
    $payload = $job->payload;

    expect($approvedBooking->refresh()->booking_approved_webhook_sent_at)->not->toBeNull()
        ->and($job->queue)->toBe('webhooks')
        ->and($job->requestTimeout)->toBe(10)
        ->and($job->headers)->toHaveKey('Signature')
        ->and($payload['event'])->toBe('booking.approved')
        ->and($payload['data']['booking']['id'])->toBe($approvedBooking->id)
        ->and($payload['data']['booking']['reference'])->toBe('BRH-WEBHOOK')
        ->and($payload['data']['booking']['status'])->toBe('approved')
        ->and($payload['data']['booking']['customer_panel_url'])->toBe(route('customer.bookings.show', $approvedBooking))
        ->and($payload['data']['customer']['phone'])->toBe('+96891234567')
        ->and($payload['data']['event']['name'])->toBe('Webhook Camp')
        ->and($payload['data']['participants'][0]['name'])->toBe('Maha Webhook')
        ->and($payload['data']['participants'][0]['contract_status'])->toBe('awaiting_signature')
        ->and($payload['data']['pricing']['unit_price'])->toBe('489.000')
        ->and($payload['data']['pricing']['total'])->toBe('489.000');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('booking approval does not queue a webhook when url is empty', function () {
    [$booking, $staff] = byruhaaWebhookBookingFixture();

    Queue::fake();

    $approvedBooking = app(BookingApprovalService::class)->approve($booking, $staff);

    Queue::assertNotPushed(CallWebhookJob::class);
    expect($approvedBooking->refresh()->booking_approved_webhook_sent_at)->toBeNull();
});

test('paid thawani confirmation queues a payment webhook with paid booking status', function () {
    $payment = byruhaaWebhookPaymentFixture(amountBaisa: 489000);

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    byruhaaFakePaidThawaniSession($payment);
    Queue::fake();

    app(ConfirmThawaniPayment::class)->confirm($payment);

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/payment-paid');
    $payload = $job->payload;
    $payment->refresh();

    expect($payment->payment_paid_webhook_sent_at)->not->toBeNull()
        ->and($payload['event'])->toBe('payment.paid')
        ->and($payload['data']['payment']['id'])->toBe($payment->id)
        ->and($payload['data']['payment']['provider'])->toBe('thawani')
        ->and($payload['data']['payment']['status'])->toBe('paid')
        ->and($payload['data']['payment']['provider_status'])->toBe('paid')
        ->and($payload['data']['payment']['amount'])->toBe('489.000')
        ->and($payload['data']['payment']['provider_reference'])->toBe('payment_'.$payment->id)
        ->and($payload['data']['booking']['payment_status'])->toBe('paid')
        ->and($payload['data']['booking']['customer_panel_url'])->toBe(route('customer.bookings.show', $payment->bookingInstallment->paymentSchedule->booking))
        ->and($payload['data'])->not->toHaveKey('installments');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('partial payment webhook includes installment summary and items', function () {
    $payment = byruhaaWebhookPaymentFixture(amountBaisa: 200000, remainingAmountBaisa: 289000);

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    byruhaaFakePaidThawaniSession($payment);
    Queue::fake();

    app(ConfirmThawaniPayment::class)->confirm($payment);

    $payload = byruhaaQueuedWebhook('https://partner.test/webhooks/payment-paid')->payload;

    expect($payload['data']['booking']['payment_status'])->toBe('partially_paid')
        ->and($payload['data']['installments']['paid_installments_count'])->toBe(1)
        ->and($payload['data']['installments']['total_installments_count'])->toBe(2)
        ->and($payload['data']['installments']['paid_amount'])->toBe('200.000')
        ->and($payload['data']['installments']['remaining_amount'])->toBe('289.000')
        ->and($payload['data']['installments']['items'][0]['status'])->toBe('paid')
        ->and($payload['data']['installments']['items'][0]['amount'])->toBe('200.000')
        ->and($payload['data']['installments']['items'][1]['status'])->toBe('pending')
        ->and($payload['data']['installments']['items'][1]['amount'])->toBe('289.000');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('repeated paid thawani confirmation does not queue duplicate webhooks', function () {
    $payment = byruhaaWebhookPaymentFixture(amountBaisa: 489000);

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    byruhaaFakePaidThawaniSession($payment);
    Queue::fake();

    $confirmedPayment = app(ConfirmThawaniPayment::class)->confirm($payment);
    app(ConfirmThawaniPayment::class)->confirm($confirmedPayment->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
});

/**
 * @return array{0: Booking, 1: User}
 */
function byruhaaWebhookBookingFixture(): array
{
    $staff = User::factory()->create();
    $customer = Customer::factory()->create([
        'name' => 'Mona Webhook',
        'email' => 'mona.webhook@example.com',
        'phone_number' => '+96891234567',
    ]);
    $event = Event::factory()->create([
        'name' => 'Webhook Camp',
        'seat_capacity' => 10,
        'price_baisa' => 489000,
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create([
        'name' => 'Maha Webhook',
    ]);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'reference' => 'BRH-WEBHOOK',
        'unit_price_baisa' => 489000,
        'family_member_count' => 1,
        'subtotal_baisa' => 489000,
        'discount_amount_baisa' => 0,
        'total_baisa' => 489000,
        'currency' => 'OMR',
    ]);

    BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    return [$booking, $staff];
}

/**
 * @return array{0: Customer, 1: Event, 2: Collection<int, FamilyMember>}
 */
function byruhaaBookingCreationFixture(): array
{
    $customer = Customer::factory()->create([
        'name' => 'Created Booking Customer',
        'email' => 'created.booking@example.com',
        'phone_number' => '+96892345678',
    ]);
    $event = Event::factory()->create([
        'name' => 'Created Booking Camp',
        'seat_capacity' => 10,
        'price_baisa' => 12000,
        'minimum_age' => 9,
        'maximum_age' => 16,
    ]);
    $familyMembers = FamilyMember::factory()
        ->count(2)
        ->sequence(
            ['name' => 'First Created Member', 'birth_date' => now()->subYears(12)->toDateString()],
            ['name' => 'Second Created Member', 'birth_date' => now()->subYears(13)->toDateString()],
        )
        ->for($customer)
        ->create();

    return [$customer, $event, $familyMembers];
}

function byruhaaWebhookPaymentFixture(int $amountBaisa, ?int $remainingAmountBaisa = null): Payment
{
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'Payment Webhook Camp']);
    $totalBaisa = $amountBaisa + ($remainingAmountBaisa ?? 0);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'reference' => 'BRH-PAY-WEBHOOK-'.$amountBaisa,
        'state' => Approved::$name,
        'currency' => 'OMR',
        'subtotal_baisa' => $totalBaisa,
        'total_baisa' => $totalBaisa,
    ]);
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'event_payment_plan_id' => null,
        'plan_name' => 'Webhook payment plan',
        'currency' => 'OMR',
        'subtotal_baisa' => $totalBaisa,
        'total_baisa' => $totalBaisa,
    ]);
    $firstInstallment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'First',
        'sequence' => 1,
        'percentage' => $remainingAmountBaisa === null ? 100 : 50,
        'amount_baisa' => $amountBaisa,
        'currency' => 'OMR',
        'state' => BookingInstallmentState::Pending,
    ]);

    if ($remainingAmountBaisa !== null) {
        BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
            'name' => 'Final',
            'sequence' => 2,
            'percentage' => 50,
            'amount_baisa' => $remainingAmountBaisa,
            'currency' => 'OMR',
            'state' => BookingInstallmentState::Pending,
        ]);
    }

    return Payment::factory()->for($firstInstallment, 'bookingInstallment')->create([
        'amount_baisa' => $amountBaisa,
        'currency' => 'OMR',
        'state' => PaymentState::Pending,
        'provider_session_id' => 'checkout_session_'.$amountBaisa,
    ]);
}

function byruhaaFakePaidThawaniSession(Payment $payment): void
{
    Http::preventStrayRequests();
    Http::fake([
        "https://uatcheckout.thawani.om/api/v1/checkout/session/{$payment->provider_session_id}" => Http::response([
            'success' => true,
            'data' => [
                'session_id' => $payment->provider_session_id,
                'payment_status' => 'paid',
                'payment_id' => 'payment_'.$payment->id,
                'invoice' => 'invoice_'.$payment->id,
                'total_amount' => $payment->amount_baisa,
            ],
        ]),
    ]);
}

function byruhaaQueuedWebhook(string $url): CallWebhookJob
{
    $queuedJob = null;

    Queue::assertPushed(CallWebhookJob::class, function (CallWebhookJob $job) use (&$queuedJob, $url): bool {
        if ($job->webhookUrl !== $url) {
            return false;
        }

        $queuedJob = $job;

        return true;
    });

    expect($queuedJob)->toBeInstanceOf(CallWebhookJob::class);

    return $queuedJob;
}
