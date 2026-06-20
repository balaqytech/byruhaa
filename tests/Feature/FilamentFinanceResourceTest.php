<?php

use App\Enums\BookingInstallmentState;
use App\Enums\LedgerAccountType;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Filament\Resources\PaymentRefunds\PaymentRefundResource;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

test('staff can list and view payments with booking customer event refunds payloads and ledger context', function () {
    $staff = User::factory()->create();
    [$payment, $booking, $customer, $event] = filamentFinancePaymentFixture([
        'reference' => 'PAY-FINANCE-1',
        'amount_baisa' => 9000,
        'state' => PaymentState::PartiallyRefunded,
        'provider_session_id' => 'checkout_session_finance',
        'provider_payment_id' => 'payment_finance_1',
        'provider_invoice' => 'invoice_finance_1',
        'provider_payment_status' => 'paid',
        'request_payload' => ['client_reference_id' => 'PAY-FINANCE-1'],
        'response_payload' => ['description' => 'Payment accepted'],
        'verified_at' => '2026-06-20 10:00:00',
        'paid_at' => '2026-06-20 10:01:00',
    ]);

    PaymentRefund::factory()
        ->for($payment)
        ->create([
            'reference' => 'REF-FINANCE-1',
            'amount_baisa' => 3000,
            'state' => PaymentRefundState::Succeeded,
            'reason' => 'Partial adjustment',
            'processed_at' => '2026-06-20 11:00:00',
        ]);

    filamentFinancePostLedger($payment);

    $this->actingAs($staff, 'web')
        ->get(PaymentResource::getUrl('index'))
        ->assertOk()
        ->assertSee('PAY-FINANCE-1')
        ->assertSee($booking->reference)
        ->assertSee($customer->name)
        ->assertSee('OMR 9.000');

    $this->actingAs($staff, 'web')
        ->get(PaymentResource::getUrl('view', ['record' => $payment]))
        ->assertOk()
        ->assertSee('PAY-FINANCE-1')
        ->assertSee($event->name)
        ->assertSee('checkout_session_finance')
        ->assertSee('REF-FINANCE-1')
        ->assertSee('Partial adjustment')
        ->assertSee('Payment accepted');
});

test('staff can search and filter payments in filament', function () {
    $staff = User::factory()->create();
    [$paidPayment] = filamentFinancePaymentFixture([
        'reference' => 'PAY-SEARCH-YES',
        'provider' => 'thawani',
        'state' => PaymentState::Paid,
    ], bookingReference: 'BRH-SEARCH-YES', customerName: 'Aisha Searchable');
    [$manualPayment] = filamentFinancePaymentFixture([
        'reference' => 'PAY-SEARCH-NO',
        'provider' => 'manual',
        'state' => PaymentState::Pending,
    ], bookingReference: 'BRH-SEARCH-NO', customerName: 'Noura Hidden');

    $this->actingAs($staff, 'web');

    Livewire::test(ListPayments::class)
        ->assertCanSeeTableRecords([$paidPayment, $manualPayment])
        ->searchTable('PAY-SEARCH-YES')
        ->assertCanSeeTableRecords([$paidPayment])
        ->assertCanNotSeeTableRecords([$manualPayment])
        ->searchTable('Aisha Searchable')
        ->assertCanSeeTableRecords([$paidPayment])
        ->assertCanNotSeeTableRecords([$manualPayment])
        ->searchTable('BRH-SEARCH-YES')
        ->assertCanSeeTableRecords([$paidPayment])
        ->assertCanNotSeeTableRecords([$manualPayment])
        ->searchTable('')
        ->filterTable('state', PaymentState::Paid->value)
        ->assertCanSeeTableRecords([$paidPayment])
        ->assertCanNotSeeTableRecords([$manualPayment])
        ->filterTable('provider', 'thawani')
        ->assertCanSeeTableRecords([$paidPayment])
        ->assertCanNotSeeTableRecords([$manualPayment]);
});

test('staff can refund a paid payment from the payment resource', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => [
                'refund_id' => 'refund_filament_1',
                'status' => 'succeeded',
            ],
        ]),
    ]);

    $staff = User::factory()->create();
    [$payment] = filamentFinancePaymentFixture([
        'reference' => 'PAY-REFUND-FILAMENT',
        'amount_baisa' => 9000,
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment_refund_filament',
        'paid_at' => '2026-06-20 10:00:00',
    ]);

    $this->actingAs($staff, 'web');

    Livewire::test(ListPayments::class)
        ->assertActionVisible(TestAction::make('refund')->table($payment))
        ->callAction(TestAction::make('refund')->table($payment), [
            'amount' => '4.000',
            'reason' => 'Admin adjustment',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $refund = $payment->refunds()->firstOrFail();

    expect($refund->state)->toBe(PaymentRefundState::Succeeded)
        ->and($refund->amount_baisa)->toBe(4000)
        ->and($refund->reason)->toBe('Admin adjustment')
        ->and($refund->ledgerTransaction()->exists())->toBeTrue()
        ->and($payment->refresh()->state)->toBe(PaymentState::PartiallyRefunded)
        ->and($payment->refundableAmountBaisa())->toBe(5000);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://uatcheckout.thawani.om/api/v1/refunds'
            && $request->hasHeader('thawani-api-key', 'test_secret_key')
            && $request['payment_id'] === 'payment_refund_filament'
            && $request['amount'] === 4000
            && $request['reason'] === 'Admin adjustment';
    });
});

test('refund action is hidden or validation blocked when payment is not refundable', function () {
    Http::preventStrayRequests();

    $staff = User::factory()->create();
    [$pendingPayment] = filamentFinancePaymentFixture([
        'reference' => 'PAY-PENDING-NO-REFUND',
        'state' => PaymentState::Pending,
    ], bookingReference: 'BRH-PENDING-NO-REFUND');
    [$paidPayment] = filamentFinancePaymentFixture([
        'reference' => 'PAY-OVER-REFUND',
        'amount_baisa' => 5000,
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment_over_refund',
    ], bookingReference: 'BRH-OVER-REFUND');

    $this->actingAs($staff, 'web');

    Livewire::test(ListPayments::class)
        ->assertActionHidden(TestAction::make('refund')->table($pendingPayment))
        ->callAction(TestAction::make('refund')->table($paidPayment), [
            'amount' => '6.000',
            'reason' => 'Too much',
        ])
        ->assertHasActionErrors(['amount']);

    expect($paidPayment->refunds()->exists())->toBeFalse();
});

test('staff can list and view refunds with provider payloads', function () {
    $staff = User::factory()->create();
    [$payment] = filamentFinancePaymentFixture(['reference' => 'PAY-FOR-REFUND']);
    $refund = PaymentRefund::factory()
        ->for($payment)
        ->create([
            'reference' => 'REF-FILAMENT-VIEW',
            'amount_baisa' => 2500,
            'state' => PaymentRefundState::Failed,
            'provider_status' => 'failed',
            'reason' => 'Rejected by gateway',
            'request_payload' => ['payment_id' => 'payment_for_refund'],
            'response_payload' => ['description' => 'Refund rejected'],
            'processed_at' => '2026-06-20 12:00:00',
        ]);

    $this->actingAs($staff, 'web')
        ->get(PaymentRefundResource::getUrl('index'))
        ->assertOk()
        ->assertSee('REF-FILAMENT-VIEW')
        ->assertSee('PAY-FOR-REFUND')
        ->assertSee('Rejected by gateway');

    $this->actingAs($staff, 'web')
        ->get(PaymentRefundResource::getUrl('view', ['record' => $refund]))
        ->assertOk()
        ->assertSee('REF-FILAMENT-VIEW')
        ->assertSee('Refund rejected')
        ->assertSee('PAY-FOR-REFUND');
});

test('staff can list and view ledger accounts and transactions with entries', function () {
    $staff = User::factory()->create();
    [$payment] = filamentFinancePaymentFixture([
        'reference' => 'PAY-LEDGER-VIEW',
        'amount_baisa' => 7000,
        'state' => PaymentState::Paid,
    ]);
    $transaction = filamentFinancePostLedger($payment);
    $account = LedgerAccount::query()->where('code', LedgerAccount::THAWANI_CLEARING_CODE)->firstOrFail();

    $this->actingAs($staff, 'web')
        ->get(LedgerAccountResource::getUrl('index'))
        ->assertOk()
        ->assertSee(LedgerAccount::THAWANI_CLEARING_CODE)
        ->assertSee('Thawani clearing');

    $this->actingAs($staff, 'web')
        ->get(LedgerAccountResource::getUrl('view', ['record' => $account]))
        ->assertOk()
        ->assertSee('LED-PAY-LEDGER-VIEW')
        ->assertSee('OMR 7.000');

    $this->actingAs($staff, 'web')
        ->get(LedgerTransactionResource::getUrl('index'))
        ->assertOk()
        ->assertSee('LED-PAY-LEDGER-VIEW')
        ->assertSee('PAY-LEDGER-VIEW');

    $this->actingAs($staff, 'web')
        ->get(LedgerTransactionResource::getUrl('view', ['record' => $transaction]))
        ->assertOk()
        ->assertSee(LedgerAccount::THAWANI_CLEARING_CODE)
        ->assertSee(LedgerAccount::CUSTOMER_DEPOSITS_CODE)
        ->assertSee('OMR 7.000');
});

test('finance admin translations are available', function () {
    app()->setLocale('en');

    expect(__('admin.navigation.finance'))->toBe('Finance')
        ->and(__('admin.resources.payments.navigation_label'))->toBe('Payments')
        ->and(__('admin.resources.ledger_transactions.navigation_label'))->toBe('Ledger transactions')
        ->and(__('admin.actions.refund'))->toBe('Refund');

    app()->setLocale('ar');

    expect(__('admin.navigation.finance'))->toBe('المالية')
        ->and(__('admin.resources.payments.navigation_label'))->toBe('الدفعات')
        ->and(__('admin.resources.ledger_transactions.navigation_label'))->toBe('قيود دفتر الأستاذ')
        ->and(__('admin.actions.refund'))->toBe('استرداد');
});

/**
 * @param  array<string, mixed>  $paymentOverrides
 * @return array{0: Payment, 1: Booking, 2: Customer, 3: Event, 4: BookingInstallment}
 */
function filamentFinancePaymentFixture(
    array $paymentOverrides = [],
    ?string $bookingReference = null,
    string $customerName = 'Finance Guardian',
): array {
    $bookingReference ??= 'BRH-FINANCE-'.Str::upper(Str::random(8));

    $customer = Customer::factory()->create(['name' => $customerName]);
    $event = Event::factory()->create(['name' => 'Finance Event']);
    $booking = Booking::factory()
        ->for($customer)
        ->for($event)
        ->create([
            'reference' => $bookingReference,
            'currency' => 'OMR',
            'subtotal_baisa' => 9000,
            'total_baisa' => 9000,
        ]);
    $schedule = BookingPaymentSchedule::factory()
        ->for($booking)
        ->create([
            'currency' => 'OMR',
            'subtotal_baisa' => 9000,
            'total_baisa' => 9000,
        ]);
    $installment = BookingInstallment::factory()
        ->for($schedule, 'paymentSchedule')
        ->create([
            'name' => 'Deposit',
            'amount_baisa' => $paymentOverrides['amount_baisa'] ?? 9000,
            'state' => BookingInstallmentState::Paid,
            'paid_at' => '2026-06-20 10:00:00',
        ]);
    $payment = Payment::factory()
        ->for($installment, 'bookingInstallment')
        ->create([
            'reference' => 'PAY-FINANCE-DEFAULT',
            'amount_baisa' => $installment->amount_baisa,
            'currency' => 'OMR',
            'state' => PaymentState::Paid,
            'provider_session_id' => 'checkout_'.$bookingReference,
            'provider_payment_id' => 'payment_'.$bookingReference,
            'provider_payment_status' => 'paid',
            'paid_at' => '2026-06-20 10:01:00',
            ...$paymentOverrides,
        ]);

    return [$payment, $booking, $customer, $event, $installment];
}

function filamentFinancePostLedger(Payment $payment): LedgerTransaction
{
    $assetAccount = LedgerAccount::query()->firstOrCreate(
        ['code' => LedgerAccount::THAWANI_CLEARING_CODE],
        [
            'name' => 'Thawani clearing',
            'type' => LedgerAccountType::Asset,
            'currency' => $payment->currency,
            'is_active' => true,
        ],
    );
    $liabilityAccount = LedgerAccount::query()->firstOrCreate(
        ['code' => LedgerAccount::CUSTOMER_DEPOSITS_CODE],
        [
            'name' => 'Customer deposits',
            'type' => LedgerAccountType::Liability,
            'currency' => $payment->currency,
            'is_active' => true,
        ],
    );

    $transaction = $payment->ledgerTransaction()->create([
        'reference' => 'LED-'.$payment->reference,
        'description' => 'Thawani payment '.$payment->reference,
        'occurred_at' => $payment->paid_at ?? now(),
        'currency' => $payment->currency,
        'total_baisa' => $payment->amount_baisa,
    ]);

    $transaction->entries()->createMany([
        [
            'ledger_account_id' => $assetAccount->id,
            'debit_baisa' => $payment->amount_baisa,
            'credit_baisa' => 0,
            'currency' => $payment->currency,
            'memo' => 'Payment '.$payment->reference,
        ],
        [
            'ledger_account_id' => $liabilityAccount->id,
            'debit_baisa' => 0,
            'credit_baisa' => $payment->amount_baisa,
            'currency' => $payment->currency,
            'memo' => 'Payment '.$payment->reference,
        ],
    ]);

    return $transaction->refresh();
}
