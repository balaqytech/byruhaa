<?php

use App\Actions\PostPaymentLedgerTransaction;
use App\Enums\BookingInstallmentState;
use App\Enums\LedgerAccountType;
use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Event;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Modules\Identity\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

test('paid payment posts a balanced double entry ledger transaction', function () {
    $payment = paidPaymentFixture(amountBaisa: 9001);

    $ledgerTransaction = app(PostPaymentLedgerTransaction::class)->execute($payment);
    $entries = $ledgerTransaction->entries()->with('ledgerAccount')->get();

    expect($ledgerTransaction)
        ->reference->toBe('LED-'.$payment->reference)
        ->total_baisa->toBe(9001)
        ->and($entries)->toHaveCount(2)
        ->and($entries->sum('debit_baisa'))->toBe(9001)
        ->and($entries->sum('credit_baisa'))->toBe(9001);

    $debitEntry = $entries->firstWhere('debit_baisa', 9001);
    $creditEntry = $entries->firstWhere('credit_baisa', 9001);

    expect($debitEntry?->ledgerAccount)
        ->code->toBe(LedgerAccount::THAWANI_CLEARING_CODE)
        ->type->toBe(LedgerAccountType::Asset)
        ->and($creditEntry?->ledgerAccount)
        ->code->toBe(LedgerAccount::CUSTOMER_DEPOSITS_CODE)
        ->type->toBe(LedgerAccountType::Liability);
});

test('ledger posting is idempotent for the same payment', function () {
    $payment = paidPaymentFixture(amountBaisa: 5000);

    $firstLedgerTransaction = app(PostPaymentLedgerTransaction::class)->execute($payment);
    $secondLedgerTransaction = app(PostPaymentLedgerTransaction::class)->execute($payment);

    expect($secondLedgerTransaction->id)->toBe($firstLedgerTransaction->id)
        ->and(LedgerEntry::query()->count())->toBe(2);
});

test('pending payments cannot be posted to the ledger', function () {
    $payment = paidPaymentFixture(amountBaisa: 5000);
    $payment->forceFill(['state' => PaymentState::Pending])->save();

    expect(fn () => app(PostPaymentLedgerTransaction::class)->execute($payment))
        ->toThrow(RuntimeException::class);
});

test('paid thawani confirmation posts ledger entries once', function () {
    Http::preventStrayRequests();

    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);

    $payment = paidPaymentFixture(amountBaisa: 7000);
    $payment->forceFill([
        'state' => PaymentState::Pending,
        'paid_at' => null,
        'provider_session_id' => 'checkout_session_paid',
    ])->save();

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/checkout_session_paid' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_session_paid',
                'payment_status' => 'paid',
                'invoice' => 'invoice_ledger',
                'total_amount' => 7000,
            ],
        ]),
    ]);

    $customer = $payment->bookingInstallment->paymentSchedule->booking->customer;

    $this->actingAs($customer, 'customer')
        ->get(URL::signedRoute('payments.thawani.success', ['payment' => $payment]))
        ->assertRedirect(route('customer.bookings.show', $payment->bookingInstallment->paymentSchedule->booking));

    expect($payment->refresh())
        ->state->toBe(PaymentState::Paid)
        ->and($payment->ledgerTransaction()->exists())->toBeTrue()
        ->and($payment->ledgerTransaction()->firstOrFail()->entries()->sum('debit_baisa'))->toBe(7000)
        ->and($payment->bookingInstallment->refresh()->state)->toBe(BookingInstallmentState::Paid);
});

function paidPaymentFixture(int $amountBaisa): Payment
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
    ]);
}
