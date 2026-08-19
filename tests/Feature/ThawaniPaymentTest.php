<?php

use App\Enums\BookingInstallmentState;
use App\Enums\PaymentProvider;
use App\Enums\PaymentState;
use App\Enums\ThawaniWebhookEventStatus;
use App\Models\WebhookDelivery;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Affiliates\Models\AffiliateReferral;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventContract;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Models\EventPaymentPlanInstallment;
use App\Modules\Events\States\Booking\Approved;
use App\Modules\Events\States\Contract\Signed;
use App\Modules\Finance\Actions\InitiateInstallmentPayment;
use App\Modules\Finance\Contracts\PaymentGateway;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\ThawaniWebhookEvent;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\WebhookServer\CallWebhookJob;

beforeEach(function () {
    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
});

test('default payment gateway resolves through the service container', function () {
    expect(app(PaymentGateway::class)->name())->toBe('thawani');
});

test('thawani payment initiation fails closed when credentials are missing', function (): void {
    Http::preventStrayRequests();
    config([
        'thawani.test.secret_key' => null,
        'thawani.test.publishable_key' => null,
    ]);

    expect(fn () => app(PaymentGateway::class)->createSession([
        'client_reference_id' => 'test-reference',
        'mode' => 'payment',
        'products' => [['name' => 'Test', 'quantity' => 1, 'unit_amount' => 100]],
        'success_url' => 'https://example.test/success',
        'cancel_url' => 'https://example.test/cancel',
        'metadata' => [],
    ]))->toThrow(RuntimeException::class, 'Thawani secret key is not configured.');
});

test('live thawani mode is blocked in staging', function (): void {
    Http::preventStrayRequests();
    config([
        'app.env' => 'staging',
        'thawani.mode' => 'live',
        'thawani.live.secret_key' => 'test-secret-only',
        'thawani.live.publishable_key' => 'test-publishable-only',
    ]);

    expect(fn () => app(PaymentGateway::class)->checkoutUrl('session'))
        ->toThrow(RuntimeException::class, 'Live Thawani payments are disabled in staging.');
});

test('thawani retries connection, rate-limit and temporary server failures only', function (): void {
    Http::preventStrayRequests();
    config([
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
    ]);
    Http::fakeSequence('https://uatcheckout.thawani.om/api/v1/checkout/session')
        ->pushStatus(429)
        ->pushStatus(503)
        ->push(['success' => true, 'data' => ['session_id' => 'retry-session']]);

    app(PaymentGateway::class)->createSession([
        'client_reference_id' => 'retry-reference',
        'mode' => 'payment',
        'products' => [['name' => 'Test', 'quantity' => 1, 'unit_amount' => 100]],
        'success_url' => 'https://example.test/success',
        'cancel_url' => 'https://example.test/cancel',
        'metadata' => [],
    ]);

    Http::assertSentCount(3);
});

test('customer can initiate a thawani checkout for the next installment', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_123',
            ],
        ]),
    ]);

    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 4500);

    $payment = app(InitiateInstallmentPayment::class)->execute($installment, $customer->id);

    expect($payment->state)->toBe(PaymentState::Pending)
        ->and($payment->provider_session_id)->toBe('checkout_session_123')
        ->and($payment->checkout_url)->toBe('https://uatcheckout.thawani.om/pay/checkout_session_123?key=test_publishable_key')
        ->and($payment->amount_baisa)->toBe(4500);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://uatcheckout.thawani.om/api/v1/checkout/session'
            && $request->hasHeader('thawani-api-key', 'test_secret_key')
            && $request['mode'] === 'payment'
            && $request['products'][0]['unit_amount'] === 4500
            && str_starts_with((string) $request['success_url'], url('/payments/thawani/'));
    });
});

test('customer reuses an active pending checkout for the same installment', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_123',
            ],
        ]),
    ]);

    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 4500);

    $firstPayment = app(InitiateInstallmentPayment::class)->execute($installment, $customer->id);
    $secondPayment = app(InitiateInstallmentPayment::class)->execute($installment, $customer->id);

    expect($secondPayment->id)->toBe($firstPayment->id)
        ->and($installment->payments()->count())->toBe(1);

    Http::assertSentCount(1);
});

test('zero amount installments are settled without a gateway checkout', function () {
    Http::preventStrayRequests();

    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 0);

    $payment = app(InitiateInstallmentPayment::class)->execute($installment, $customer->id);

    expect($payment->state)->toBe(PaymentState::Paid)
        ->and($payment->provider)->toBe(PaymentProvider::Manual)
        ->and($payment->amount_baisa)->toBe(0)
        ->and($payment->checkout_url)->toBeNull()
        ->and($payment->ledgerTransaction()->exists())->toBeFalse()
        ->and($installment->refresh())
        ->state->toBe(BookingInstallmentState::Paid)
        ->paid_at->not->toBeNull();

    Http::assertNothingSent();
});

test('booking page shows thawani payment action for the next installment', function () {
    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 4500);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $installment->paymentSchedule->booking])
        ->assertSee(__('ui.payments.pay_with_thawani'))
        ->assertSee('First');
});

test('booking page offers full payment by default without installment plans', function () {
    [$customer, $booking] = payableBookingFixture(amountBaisa: 12000);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->assertSee(__('ui.payments.full_payment'))
        ->assertSee(__('ui.payments.pay_full_amount'))
        ->assertDontSee(__('ui.payments.no_plans'));
});

test('customer can initiate a full thawani payment by default', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_full_payment',
            ],
        ]),
    ]);

    [$customer, $booking] = payableBookingFixture(amountBaisa: 12000);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->call('payInFull')
        ->assertRedirect('https://uatcheckout.thawani.om/pay/checkout_full_payment?key=test_publishable_key');

    $schedule = $booking->refresh()->paymentSchedule()->with('installments.payments')->firstOrFail();
    $installment = $schedule->installments->first();

    expect($schedule)
        ->event_payment_plan_id->toBeNull()
        ->plan_name->toBe(__('ui.payments.full_payment'))
        ->total_baisa->toBe(12000)
        ->and($installment)
        ->not->toBeNull()
        ->percentage->toBe(100)
        ->amount_baisa->toBe(12000)
        ->and($installment->payments)
        ->toHaveCount(1)
        ->and($installment->payments->first())
        ->provider_session_id->toBe('checkout_full_payment');
});

test('customer can complete a zero total booking without a gateway checkout', function () {
    Http::preventStrayRequests();

    [$customer, $booking] = payableBookingFixture(amountBaisa: 12000);

    $booking->forceFill([
        'discount_amount_baisa' => 12000,
        'total_baisa' => 0,
    ])->save();

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->assertSee(__('ui.payments.free_booking'))
        ->assertSee(__('ui.payments.free_booking_description'))
        ->assertSee(__('ui.payments.complete_free_booking'))
        ->assertDontSee(__('ui.payments.pay_full_amount'))
        ->assertDontSee(__('ui.payments.full_payment_description'))
        ->call('payInFull')
        ->assertHasNoErrors()
        ->assertSee(__('ui.payments.completed'));

    $schedule = $booking->refresh()->paymentSchedule()->with('installments.payments')->firstOrFail();
    $installment = $schedule->installments->first();
    $payment = $installment?->payments->first();

    expect($schedule->total_baisa)->toBe(0)
        ->and($installment)->not->toBeNull()
        ->and($installment->amount_baisa)->toBe(0)
        ->and($installment->state)->toBe(BookingInstallmentState::Paid)
        ->and($payment)->not->toBeNull()
        ->and($payment->provider)->toBe(PaymentProvider::Manual)
        ->and($payment->state)->toBe(PaymentState::Paid)
        ->and($payment->checkout_url)->toBeNull()
        ->and($payment->ledgerTransaction()->exists())->toBeFalse();

    Http::assertNothingSent();
});

test('customer cannot select an installment plan for a zero total booking', function () {
    [$customer, $booking] = payableBookingFixture(amountBaisa: 12000);

    $booking->forceFill([
        'discount_amount_baisa' => 12000,
        'total_baisa' => 0,
    ])->save();

    $paymentPlan = EventPaymentPlan::factory()->for($booking->event)->create([
        'name' => 'Two payments',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'sequence' => 1,
        'percentage' => 50,
        'due_date' => now()->toDateString(),
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'sequence' => 2,
        'percentage' => 50,
        'due_date' => now()->addMonth()->toDateString(),
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->assertDontSee(__('ui.payments.installment_options'))
        ->set('paymentPlanId', $paymentPlan->id)
        ->call('selectPaymentPlan')
        ->assertHasErrors(['paymentPlanId']);

    expect($booking->refresh()->paymentSchedule()->exists())->toBeFalse();
});

test('failed full thawani payment shows an error and stores gateway details', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => false,
            'description' => 'Invalid API key',
            'code' => 4010,
        ], 401),
    ]);

    [$customer, $booking] = payableBookingFixture(amountBaisa: 12000);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->call('payInFull')
        ->assertHasErrors('payment')
        ->assertSee(__('ui.messages.payment_gateway_unavailable'));

    $payment = Payment::query()->latest('id')->firstOrFail();

    expect($payment->state)->toBe(PaymentState::Failed)
        ->and($payment->provider_session_id)->toBeNull()
        ->and($payment->checkout_url)->toBeNull()
        ->and($payment->response_payload['error'])->toBe('Invalid API key')
        ->and($payment->response_payload['status'])->toBe(401)
        ->and(data_get($payment->response_payload, 'response.description'))->toBe('Invalid API key')
        ->and(data_get($payment->response_payload, 'response.code'))->toBe(4010);
});

test('thawani success return marks the payment and installment paid', function () {
    Http::preventStrayRequests();

    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 9001);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 9001,
        'provider_session_id' => 'checkout_session_paid',
    ]);

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/checkout_session_paid' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_paid',
                'payment_status' => 'paid',
                'invoice' => 'invoice_123',
                'total_amount' => 9001,
            ],
        ]),
    ]);

    $this->actingAs($customer, 'customer')
        ->get(URL::signedRoute('payments.thawani.success', ['payment' => $payment]))
        ->assertRedirect(route('customer.bookings.show', $installment->paymentSchedule->booking));

    expect($payment->refresh())
        ->state->toBe(PaymentState::Paid)
        ->provider_payment_status->toBe('paid')
        ->provider_invoice->toBe('invoice_123')
        ->paid_at->not->toBeNull()
        ->and($installment->refresh())
        ->state->toBe(BookingInstallmentState::Paid)
        ->paid_at->not->toBeNull();
});

test('thawani success return does not mark unpaid sessions paid', function () {
    Http::preventStrayRequests();

    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 5000);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 5000,
        'provider_session_id' => 'checkout_session_unpaid',
    ]);

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/checkout_session_unpaid' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_unpaid',
                'payment_status' => 'unpaid',
                'total_amount' => 5000,
            ],
        ]),
    ]);

    $this->actingAs($customer, 'customer')
        ->get(URL::signedRoute('payments.thawani.success', ['payment' => $payment]))
        ->assertRedirect(route('customer.bookings.show', $installment->paymentSchedule->booking));

    expect($payment->refresh())
        ->state->toBe(PaymentState::Pending)
        ->provider_payment_status->toBe('unpaid')
        ->and($installment->refresh())
        ->state->toBe(BookingInstallmentState::Pending);
});

test('scheduled thawani reconciliation marks paid sessions paid', function () {
    Http::preventStrayRequests();

    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 9001);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 9001,
        'provider_session_id' => 'checkout_session_paid',
    ]);

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/checkout_session_paid' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_paid',
                'payment_status' => 'paid',
                'invoice' => 'invoice_123',
                'total_amount' => 9001,
            ],
        ]),
    ]);

    $this->artisan('payments:reconcile-thawani')
        ->assertSuccessful();

    expect($payment->refresh())
        ->state->toBe(PaymentState::Paid)
        ->provider_payment_status->toBe('paid')
        ->provider_invoice->toBe('invoice_123')
        ->and($installment->refresh())
        ->state->toBe(BookingInstallmentState::Paid);

    expect($payment->ledgerTransaction()->exists())->toBeTrue();
});

test('scheduled thawani reconciliation leaves unpaid sessions pending', function () {
    Http::preventStrayRequests();

    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 5000);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 5000,
        'provider_session_id' => 'checkout_session_unpaid',
    ]);

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/checkout_session_unpaid' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_unpaid',
                'payment_status' => 'unpaid',
                'total_amount' => 5000,
            ],
        ]),
    ]);

    $this->artisan('payments:reconcile-thawani')
        ->assertSuccessful();

    expect($payment->refresh())
        ->state->toBe(PaymentState::Pending)
        ->provider_payment_status->toBe('unpaid')
        ->and($installment->refresh())
        ->state->toBe(BookingInstallmentState::Pending);
});

test('thawani webhook maps common identifiers to local payments', function () {
    Http::preventStrayRequests();

    $cases = [
        'client reference' => fn (Payment $payment): array => ['client_reference_id' => $payment->reference],
        'session id' => fn (Payment $payment): array => ['data' => ['session_id' => $payment->provider_session_id]],
        'invoice' => fn (Payment $payment): array => ['invoice' => $payment->provider_invoice],
        'payment id' => fn (Payment $payment): array => ['payment_id' => $payment->provider_payment_id],
        'metadata payment id' => fn (Payment $payment): array => ['data' => ['metadata' => ['payment_id' => $payment->id]]],
    ];

    $amountBaisa = 8000;

    foreach ($cases as $name => $payloadFor) {
        $suffix = str_replace(' ', '_', $name);
        [, $installment] = paymentInstallmentFixture(amountBaisa: $amountBaisa);
        $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
            'amount_baisa' => $amountBaisa,
            'provider_session_id' => 'checkout_'.$suffix,
            'provider_invoice' => 'invoice_'.$suffix,
            'provider_payment_id' => 'payment_'.$suffix,
        ]);

        fakeThawaniSession($payment);

        $this->postJson(route('api.webhooks.thawani'), $payloadFor($payment))
            ->assertOk()
            ->assertJsonPath('status', ThawaniWebhookEventStatus::Processed->value)
            ->assertJsonPath('payment_id', $payment->id);

        expect($payment->refresh()->state)->toBe(PaymentState::Paid)
            ->and($installment->refresh()->state)->toBe(BookingInstallmentState::Paid);

        $amountBaisa++;
    }

    expect(ThawaniWebhookEvent::query()->count())->toBe(count($cases));
});

test('thawani webhook does not trust paid payload when retrieved session is unpaid', function () {
    Http::preventStrayRequests();

    [, $installment] = paymentInstallmentFixture(amountBaisa: 5000);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 5000,
        'provider_session_id' => 'checkout_payload_paid_provider_unpaid',
    ]);

    fakeThawaniSession($payment, ['payment_status' => 'unpaid']);

    $this->postJson(route('api.webhooks.thawani'), [
        'client_reference_id' => $payment->reference,
        'payment_status' => 'paid',
        'total_amount' => 5000,
    ])
        ->assertOk()
        ->assertJsonPath('status', ThawaniWebhookEventStatus::Processed->value);

    expect($payment->refresh())
        ->state->toBe(PaymentState::Pending)
        ->provider_payment_status->toBe('unpaid')
        ->and($installment->refresh()->state)->toBe(BookingInstallmentState::Pending);
});

test('thawani webhook rejects paid sessions with mismatched verification data', function () {
    Http::preventStrayRequests();

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    Queue::fake();

    $cases = [
        'amount mismatch' => ['total_amount' => 5001],
        'reference mismatch' => ['client_reference_id' => 'PAY-WRONGREF123'],
        'missing amount' => ['total_amount' => null],
    ];

    foreach ($cases as $name => $sessionOverrides) {
        $suffix = str_replace(' ', '_', $name);
        [, $installment] = paymentInstallmentFixture(amountBaisa: 5000);
        $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
            'amount_baisa' => 5000,
            'provider_session_id' => 'checkout_rejected_'.$suffix,
        ]);

        fakeThawaniSession($payment, $sessionOverrides);

        $this->postJson(route('api.webhooks.thawani'), [
            'client_reference_id' => $payment->reference,
        ])
            ->assertAccepted()
            ->assertJsonPath('status', ThawaniWebhookEventStatus::Rejected->value);

        expect($payment->refresh()->state)->toBe(PaymentState::Failed)
            ->and($payment->ledgerTransaction()->exists())->toBeFalse()
            ->and($installment->refresh()->state)->toBe(BookingInstallmentState::Pending);
    }

    Queue::assertNotPushed(CallWebhookJob::class);
    expect(WebhookDelivery::query()->count())->toBe(0);
});

test('duplicate thawani webhooks are idempotent for side effects', function () {
    Http::preventStrayRequests();

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    [, $installment] = paymentInstallmentFixture(amountBaisa: 489000);
    $booking = $installment->paymentSchedule->booking;
    $affiliate = Affiliate::factory()->create();
    AffiliateReferral::factory()->for($affiliate)->for($booking)->create([
        'affiliate_code' => $affiliate->code,
        'affiliate_name' => $affiliate->name,
    ]);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 489000,
        'provider_session_id' => 'checkout_duplicate_webhook',
    ]);

    fakeThawaniSession($payment);
    Queue::fake();

    $payload = ['client_reference_id' => $payment->reference];

    $this->postJson(route('api.webhooks.thawani'), $payload)->assertOk();
    $this->postJson(route('api.webhooks.thawani'), $payload)->assertOk();

    Queue::assertPushed(CallWebhookJob::class, 1);
    expect($payment->refresh()->state)->toBe(PaymentState::Paid)
        ->and($payment->ledgerTransaction()->count())->toBe(1)
        ->and(AffiliateCommission::query()->where('payment_id', $payment->id)->count())->toBe(1)
        ->and(WebhookDelivery::query()->whereMorphedTo('webhookable', $payment)->where('event', 'payment.paid')->count())->toBe(1)
        ->and(ThawaniWebhookEvent::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

test('thawani webhook rejects invalid production token without contacting gateway', function () {
    Http::preventStrayRequests();

    config([
        'app.env' => 'production',
        'thawani.webhook.token' => 'valid-token',
    ]);

    [, $installment] = paymentInstallmentFixture(amountBaisa: 5000);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 5000,
        'provider_session_id' => 'checkout_invalid_token',
    ]);

    $this->postJson(route('api.webhooks.thawani', ['token' => 'wrong-token']), [
        'client_reference_id' => $payment->reference,
    ])->assertForbidden();

    expect($payment->refresh()->state)->toBe(PaymentState::Pending)
        ->and(ThawaniWebhookEvent::query()->count())->toBe(0);
});

test('thawani webhook records unmatched payloads without contacting gateway', function () {
    Http::preventStrayRequests();

    $this->postJson(route('api.webhooks.thawani'), [
        'event' => 'checkout.paid',
        'client_reference_id' => 'PAY-UNKNOWN',
        'session_id' => 'checkout_unknown',
    ])
        ->assertAccepted()
        ->assertJsonPath('status', ThawaniWebhookEventStatus::Unmatched->value)
        ->assertJsonPath('payment_id', null);

    $event = ThawaniWebhookEvent::query()->sole();

    expect($event->status)->toBe(ThawaniWebhookEventStatus::Unmatched)
        ->and($event->client_reference_id)->toBe('PAY-UNKNOWN')
        ->and($event->provider_session_id)->toBe('checkout_unknown');
});

test('thawani cancel return cancels the payment attempt only', function () {
    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 5000);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 5000,
        'provider_session_id' => 'checkout_session_cancelled',
    ]);

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/checkout_session_cancelled' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_cancelled',
                'payment_status' => 'unpaid',
                'total_amount' => 5000,
            ],
        ]),
        'https://uatcheckout.thawani.om/api/v1/checkout/checkout_session_cancelled/cancel' => Http::response([
            'success' => true,
        ]),
    ]);

    $this->actingAs($customer, 'customer')
        ->get(URL::signedRoute('payments.thawani.cancel', ['payment' => $payment]))
        ->assertRedirect(route('customer.bookings.show', $installment->paymentSchedule->booking));

    expect($payment->refresh())
        ->state->toBe(PaymentState::Cancelled)
        ->provider_payment_status->toBe('cancelled')
        ->and($installment->refresh())
        ->state->toBe(BookingInstallmentState::Pending);
});

test('customer must pay installments in order', function () {
    [$customer, $firstInstallment, $secondInstallment] = paymentInstallmentFixture(amountBaisa: 5000, withSecondInstallment: true);

    expect(fn () => app(InitiateInstallmentPayment::class)->execute($secondInstallment, $customer->id))
        ->toThrow(ValidationException::class);

    expect($firstInstallment->refresh()->state)->toBe(BookingInstallmentState::Pending);
});

/**
 * @return array{0: Customer, 1: BookingInstallment, 2?: BookingInstallment}
 */
function paymentInstallmentFixture(int $amountBaisa, bool $withSecondInstallment = false): array
{
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'Summer Adventure']);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'currency' => 'OMR',
        'subtotal_baisa' => $amountBaisa,
        'total_baisa' => $amountBaisa,
    ]);
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'currency' => 'OMR',
        'subtotal_baisa' => $amountBaisa,
        'total_baisa' => $amountBaisa,
    ]);
    $firstInstallment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'First',
        'sequence' => 1,
        'percentage' => 50,
        'amount_baisa' => $amountBaisa,
        'state' => BookingInstallmentState::Pending,
    ]);

    if (! $withSecondInstallment) {
        return [$customer, $firstInstallment];
    }

    $secondInstallment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'Second',
        'sequence' => 2,
        'percentage' => 50,
        'amount_baisa' => $amountBaisa,
        'state' => BookingInstallmentState::Pending,
    ]);

    return [$customer, $firstInstallment, $secondInstallment];
}

/**
 * @return array{0: Customer, 1: Booking}
 */
function payableBookingFixture(int $amountBaisa): array
{
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'Summer Adventure']);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'state' => Approved::$name,
        'currency' => 'OMR',
        'subtotal_baisa' => $amountBaisa,
        'total_baisa' => $amountBaisa,
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    EventContract::factory()->for($bookingFamilyMember, 'bookingFamilyMember')->create([
        'state' => Signed::$name,
        'signed_at' => now(),
    ]);

    return [$customer, $booking];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeThawaniSession(Payment $payment, array $overrides = []): void
{
    Http::fake([
        "https://uatcheckout.thawani.om/api/v1/checkout/session/{$payment->provider_session_id}" => Http::response([
            'success' => true,
            'data' => array_replace([
                'session_id' => $payment->provider_session_id,
                'client_reference_id' => $payment->reference,
                'payment_status' => 'paid',
                'payment_id' => $payment->provider_payment_id ?? 'payment_'.$payment->id,
                'invoice' => $payment->provider_invoice ?? 'invoice_'.$payment->id,
                'total_amount' => $payment->amount_baisa,
            ], $overrides),
        ]),
    ]);
}
