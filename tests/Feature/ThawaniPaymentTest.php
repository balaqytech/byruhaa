<?php

use App\Actions\InitiateInstallmentPayment;
use App\Contracts\Payments\PaymentGateway;
use App\Enums\BookingInstallmentState;
use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventContract;
use App\Models\FamilyMember;
use App\Models\Payment;
use App\States\Booking\Approved;
use App\States\Contract\Signed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

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

test('thawani cancel return cancels the payment attempt only', function () {
    [$customer, $installment] = paymentInstallmentFixture(amountBaisa: 5000);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 5000,
        'provider_session_id' => 'checkout_session_cancelled',
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
