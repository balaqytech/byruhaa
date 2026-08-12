<?php

use App\Actions\RefundPayment;
use App\Enums\BookingInstallmentState;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Notifications\PaymentRefundedNotification;
use App\Support\Money\MoneyFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
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

test('paid payment can be fully refunded through thawani and posts reversal ledger entries', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => [
                'refund_id' => 'refund_123',
                'status' => 'succeeded',
            ],
        ]),
    ]);

    $payment = refundablePaymentFixture(amountBaisa: 9001);

    $paymentRefund = app(RefundPayment::class)->execute($payment, reason: 'Family cancelled');
    $entries = $paymentRefund->ledgerTransaction()->firstOrFail()->entries()->with('ledgerAccount')->get();

    expect($paymentRefund->state)->toBe(PaymentRefundState::Succeeded)
        ->and($paymentRefund->amount_baisa)->toBe(9001)
        ->and($paymentRefund->provider_refund_id)->toBe('refund_123')
        ->and($payment->refresh()->state)->toBe(PaymentState::Refunded)
        ->and($payment->bookingInstallment->refresh()->state)->toBe(BookingInstallmentState::Pending)
        ->and($entries->sum('debit_baisa'))->toBe(9001)
        ->and($entries->sum('credit_baisa'))->toBe(9001)
        ->and($entries->firstWhere('debit_baisa', 9001)?->ledgerAccount->code)->toBe(LedgerAccount::CUSTOMER_DEPOSITS_CODE)
        ->and($entries->firstWhere('credit_baisa', 9001)?->ledgerAccount->code)->toBe(LedgerAccount::THAWANI_CLEARING_CODE);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://uatcheckout.thawani.om/api/v1/refunds'
            && $request->hasHeader('thawani-api-key', 'test_secret_key')
            && $request['payment_id'] === 'payment_123'
            && $request['amount'] === 9001
            && $request['reason'] === 'Family cancelled';
    });
});

test('successful refund notifies the customer and queues a refund webhook once', function () {
    config(['byruhaa.webhooks.payment_refunded_url' => 'https://partner.test/payment-refunded']);
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => ['refund_id' => 'refund_notified', 'status' => 'succeeded'],
        ]),
    ]);
    Notification::fake();
    Queue::fake();
    $payment = refundablePaymentFixture(amountBaisa: 7000);
    $customer = $payment->bookingInstallment->paymentSchedule->booking->customer;

    $refund = app(RefundPayment::class)->execute($payment);

    Notification::assertSentToTimes($customer, PaymentRefundedNotification::class, 1);
    Queue::assertPushed(CallWebhookJob::class, fn (CallWebhookJob $job): bool => $job->webhookUrl === 'https://partner.test/payment-refunded'
        && $job->payload['event'] === 'payment.refunded'
        && $job->payload['data']['refund']['id'] === $refund->id);
});

test('paid payment can be partially refunded and remains partially refunded', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => [
                'refund_id' => 'refund_partial',
                'status' => 'succeeded',
            ],
        ]),
    ]);

    $payment = refundablePaymentFixture(amountBaisa: 9001);

    $paymentRefund = app(RefundPayment::class)->execute($payment, 4000, 'Partial refund');

    expect($paymentRefund->state)->toBe(PaymentRefundState::Succeeded)
        ->and($paymentRefund->amount_baisa)->toBe(4000)
        ->and($payment->refresh()->state)->toBe(PaymentState::PartiallyRefunded)
        ->and($payment->refundableAmountBaisa())->toBe(5001)
        ->and(MoneyFactory::formatMinorUnits($payment->refundableAmountBaisa(), $payment->currency))->toBe('5.001')
        ->and($payment->bookingInstallment->refresh()->state)->toBe(BookingInstallmentState::Paid)
        ->and($paymentRefund->ledgerTransaction()->firstOrFail()->entries()->sum('debit_baisa'))->toBe(4000);
});

test('refund action resolves thawani payment id from invoice when missing', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/payments?checkout_invoice=invoice_123' => Http::response([
            'success' => true,
            'data' => [
                [
                    'payment_id' => 'payment_from_invoice',
                ],
            ],
        ]),
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => [
                'refund_id' => 'refund_invoice',
                'status' => 'succeeded',
            ],
        ]),
    ]);

    $payment = refundablePaymentFixture(amountBaisa: 5000);
    $payment->forceFill(['provider_payment_id' => null])->save();

    $paymentRefund = app(RefundPayment::class)->execute($payment);

    expect($paymentRefund->provider_payment_id)->toBe('payment_from_invoice')
        ->and($payment->refresh()->provider_payment_id)->toBe('payment_from_invoice');
});

test('refund action rejects amounts above remaining refundable amount', function () {
    $payment = refundablePaymentFixture(amountBaisa: 5000);

    PaymentRefund::factory()->for($payment)->create([
        'amount_baisa' => 3000,
        'state' => PaymentRefundState::Succeeded,
    ]);

    expect(fn () => app(RefundPayment::class)->execute($payment, 3000))
        ->toThrow(ValidationException::class);
});

test('refund action records failed refund attempts when thawani rejects the request', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => false,
            'description' => 'Refund rejected',
            'code' => 4220,
        ], 422),
    ]);

    $payment = refundablePaymentFixture(amountBaisa: 5000);

    expect(fn () => app(RefundPayment::class)->execute($payment))
        ->toThrow(ValidationException::class);

    $paymentRefund = $payment->refunds()->firstOrFail();

    expect($paymentRefund->state)->toBe(PaymentRefundState::Failed)
        ->and($payment->refresh()->state)->toBe(PaymentState::Paid)
        ->and($paymentRefund->response_payload['error'])->toBe('Refund rejected')
        ->and($paymentRefund->response_payload['status'])->toBe(422)
        ->and(data_get($paymentRefund->response_payload, 'response.description'))->toBe('Refund rejected')
        ->and($paymentRefund->ledgerTransaction()->exists())->toBeFalse();
});

function refundablePaymentFixture(int $amountBaisa): Payment
{
    $customer = Customer::factory()->create();
    $event = Event::factory()->create();
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
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'amount_baisa' => $amountBaisa,
        'state' => BookingInstallmentState::Paid,
        'paid_at' => now(),
    ]);

    return Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => $amountBaisa,
        'state' => PaymentState::Paid,
        'paid_at' => now(),
        'provider_session_id' => 'checkout_session_'.$amountBaisa,
        'provider_payment_id' => 'payment_123',
        'provider_invoice' => 'invoice_123',
    ]);
}
