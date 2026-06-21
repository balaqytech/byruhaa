<?php

use App\Actions\ConfirmThawaniPayment;
use App\Actions\CreateAffiliatePayoutRequest;
use App\Actions\CreateCustomerBooking;
use App\Actions\PostAffiliateCommissionForPayment;
use App\Actions\PostAffiliatePayoutLedgerTransaction;
use App\Enums\AffiliatePayoutRequestStatus;
use App\Enums\AffiliateStatus;
use App\Enums\BookingInstallmentState;
use App\Enums\LedgerAccountType;
use App\Enums\PaymentState;
use App\Filament\Resources\AffiliatePayoutRequests\Pages\ListAffiliatePayoutRequests;
use App\Filament\Resources\Affiliates\Pages\ListAffiliates;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayoutRequest;
use App\Models\AffiliateReferral;
use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'affiliate.commission_rate_basis_points' => 500,
        'affiliate.minimum_payout_baisa' => 20000,
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
});

test('affiliate registration requires phone and creates a pending account with optional email', function () {
    $this->post(route('affiliate.register.store'), [
        'name' => 'Missing Phone',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('phone_number');

    $this->post(route('affiliate.register.store'), [
        'name' => 'Aisha Affiliate',
        'email' => null,
        'phone_number' => '92345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('affiliate.pending'));

    $affiliate = Affiliate::query()->where('name', 'Aisha Affiliate')->firstOrFail();

    expect($affiliate)
        ->email->toBeNull()
        ->phone_number->toBe('+96892345678')
        ->status->toBe(AffiliateStatus::Pending)
        ->code->not->toBeEmpty()
        ->and(Hash::check('password', $affiliate->password))->toBeTrue();

    $this->assertAuthenticatedAs($affiliate, 'affiliate');
});

test('affiliate auth routes separate pending approved and blocked accounts', function () {
    $pending = Affiliate::factory()->pending()->create(['name' => 'Pending Affiliate']);
    $approved = Affiliate::factory()->create(['name' => 'Approved Affiliate', 'code' => 'APPROVED1']);
    $rejected = Affiliate::factory()->rejected()->create(['phone_number' => '+96892345670']);
    $suspended = Affiliate::factory()->suspended()->create(['phone_number' => '+96892345671']);

    $this->actingAs($pending, 'affiliate')
        ->get(route('affiliate.pending'))
        ->assertOk()
        ->assertSee(__('ui.affiliates.pending_title'));

    $this->actingAs($pending, 'affiliate')
        ->get(route('affiliate.dashboard'))
        ->assertRedirect(route('affiliate.pending'));

    $this->actingAs($approved, 'affiliate')
        ->get(route('affiliate.dashboard'))
        ->assertOk()
        ->assertSee('APPROVED1');

    $this->actingAs($rejected, 'affiliate')
        ->get(route('affiliate.dashboard'))
        ->assertForbidden();

    $this->actingAs($suspended, 'affiliate')
        ->get(route('affiliate.dashboard'))
        ->assertForbidden();

    auth('affiliate')->logout();

    $this->post(route('affiliate.login.store'), [
        'login' => $rejected->phone_number,
        'password' => 'password',
    ])->assertSessionHasErrors('login');
});

test('approved public ref code stores a ninety day attribution cookie and session', function () {
    $affiliate = Affiliate::factory()->create([
        'code' => 'REFCODE1',
        'name' => 'Referral Partner',
    ]);

    $response = $this->get(route('events.index', ['ref' => $affiliate->code]));

    $response
        ->assertOk()
        ->assertCookie('affiliate_referral');

    $payload = Session::get('affiliate.referral');

    expect($payload['affiliate_id'])->toBe($affiliate->id)
        ->and($payload['code'])->toBe('REFCODE1')
        ->and($payload['name'])->toBe('Referral Partner')
        ->and((int) Carbon::parse($payload['captured_at'])->diffInDays(Carbon::parse($payload['expires_at'])))->toBe(90);
});

test('invalid or suspended public ref code is ignored', function () {
    $suspended = Affiliate::factory()->suspended()->create(['code' => 'SUSPREF1']);

    $this->get(route('events.index', ['ref' => $suspended->code]))
        ->assertOk()
        ->assertSessionMissing('affiliate.referral')
        ->assertCookieMissing('affiliate_referral');

    $this->get(route('events.index', ['ref' => 'NOPE1234']))
        ->assertOk()
        ->assertSessionMissing('affiliate.referral')
        ->assertCookieMissing('affiliate_referral');
});

test('customer booking creates an affiliate referral snapshot from valid attribution', function () {
    [$customer, $event, $familyMember] = affiliateBookingInputFixture();
    $affiliate = Affiliate::factory()->create([
        'code' => 'SNAPREF1',
        'name' => 'Snapshot Name',
    ]);

    $booking = app(CreateCustomerBooking::class)->execute(
        $customer,
        [
            'event_id' => $event->id,
            'family_member_ids' => [$familyMember->id],
        ],
        [
            'affiliate_id' => $affiliate->id,
            'code' => 'OLDREF01',
            'name' => 'Old Snapshot',
            'captured_at' => now()->subDay()->toISOString(),
            'expires_at' => now()->addDays(89)->toISOString(),
        ],
    );

    $referral = $booking->affiliateReferral()->firstOrFail();

    expect($referral)
        ->affiliate_id->toBe($affiliate->id)
        ->affiliate_code->toBe('OLDREF01')
        ->affiliate_name->toBe('Old Snapshot')
        ->attributed_at->not->toBeNull();
});

test('paid full and partial payments create proportional affiliate commissions once', function () {
    [$affiliate, $fullPayment] = affiliatePaidPaymentFixture(amountBaisa: 50000);
    $fullCommission = app(PostAffiliateCommissionForPayment::class)->execute($fullPayment);

    expect($fullCommission)
        ->not->toBeNull()
        ->base_amount_baisa->toBe(50000)
        ->commission_rate_basis_points->toBe(500)
        ->commission_amount_baisa->toBe(2500)
        ->and($fullCommission->ledgerTransaction()->exists())->toBeTrue();

    [$affiliate, $partialPayment] = affiliatePaidPaymentFixture(
        amountBaisa: 10000,
        affiliate: $affiliate,
        bookingTotalBaisa: 50000,
    );
    $partialCommission = app(PostAffiliateCommissionForPayment::class)->execute($partialPayment);

    expect($partialCommission)
        ->not->toBeNull()
        ->base_amount_baisa->toBe(10000)
        ->commission_amount_baisa->toBe(500);

    $repeatCommission = app(PostAffiliateCommissionForPayment::class)->execute($partialPayment);

    expect($repeatCommission?->id)->toBe($partialCommission->id)
        ->and($partialPayment->affiliateCommission()->count())->toBe(1);
});

test('thawani confirmation posts affiliate commission idempotently', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/checkout_affiliate_paid' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_affiliate_paid',
                'payment_status' => 'paid',
                'invoice' => 'invoice_affiliate',
                'total_amount' => 12000,
            ],
        ]),
    ]);

    [, $payment] = affiliatePaidPaymentFixture(amountBaisa: 12000, paymentState: PaymentState::Pending);
    $payment->forceFill([
        'provider_session_id' => 'checkout_affiliate_paid',
        'paid_at' => null,
    ])->save();

    app(ConfirmThawaniPayment::class)->confirm($payment);
    app(ConfirmThawaniPayment::class)->confirm($payment->refresh());

    expect($payment->refresh())
        ->state->toBe(PaymentState::Paid)
        ->and($payment->affiliateCommission()->count())->toBe(1)
        ->and(AffiliateCommission::query()->sum('commission_amount_baisa'))->toBe(600);
});

test('payout requests require the minimum balance and reserve pending approved and paid amounts', function () {
    [$affiliate, $payment] = affiliatePaidPaymentFixture(amountBaisa: 1000000);
    app(PostAffiliateCommissionForPayment::class)->execute($payment);

    expect(fn () => app(CreateAffiliatePayoutRequest::class)->execute(
        $affiliate,
        10000,
        paymentDetails: ['details' => 'Bank account'],
    ))->toThrow(ValidationException::class);

    $payout = app(CreateAffiliatePayoutRequest::class)->execute(
        $affiliate,
        20000,
        'First payout',
        ['details' => 'Bank account'],
    );

    expect($payout)
        ->status->toBe(AffiliatePayoutRequestStatus::Pending)
        ->amount_baisa->toBe(20000)
        ->and($affiliate->refresh()->availableBalanceBaisa())->toBe(30000);

    AffiliatePayoutRequest::factory()
        ->for($affiliate)
        ->approved()
        ->create(['amount_baisa' => 10000]);

    expect($affiliate->refresh()->availableBalanceBaisa())->toBe(20000);
});

test('affiliate payout form creates a payout request from available balance', function () {
    [$affiliate, $payment] = affiliatePaidPaymentFixture(amountBaisa: 1000000);
    app(PostAffiliateCommissionForPayment::class)->execute($payment);

    $this->actingAs($affiliate, 'affiliate');

    Livewire::test('pages::affiliate.payout-request')
        ->set('amount', '20.000')
        ->set('payment_details', 'Bank transfer to account 123')
        ->set('affiliate_notes', 'Monthly payout')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('affiliate.dashboard'));

    $payout = $affiliate->payoutRequests()->firstOrFail();

    expect($payout)
        ->amount_baisa->toBe(20000)
        ->affiliate_notes->toBe('Monthly payout')
        ->payment_details->toBe(['details' => 'Bank transfer to account 123']);
});

test('affiliate commission and paid payout ledger postings are balanced and idempotent', function () {
    [$affiliate, $payment] = affiliatePaidPaymentFixture(amountBaisa: 50000);
    $commission = app(PostAffiliateCommissionForPayment::class)->execute($payment);

    $commissionLedger = $commission->ledgerTransaction()->with('entries.ledgerAccount')->firstOrFail();
    $commissionEntries = $commissionLedger->entries;

    expect($commissionEntries)
        ->toHaveCount(2)
        ->and($commissionEntries->sum('debit_baisa'))->toBe(2500)
        ->and($commissionEntries->sum('credit_baisa'))->toBe(2500)
        ->and($commissionEntries->firstWhere('debit_baisa', 2500)?->ledgerAccount)
        ->code->toBe(LedgerAccount::AFFILIATE_COMMISSION_EXPENSE_CODE)
        ->type->toBe(LedgerAccountType::Expense)
        ->and($commissionEntries->firstWhere('credit_baisa', 2500)?->ledgerAccount)
        ->code->toBe(LedgerAccount::AFFILIATE_COMMISSION_LIABILITY_CODE)
        ->type->toBe(LedgerAccountType::Liability);

    app(PostAffiliateCommissionForPayment::class)->execute($payment);

    expect($commission->ledgerTransaction()->count())->toBe(1)
        ->and(LedgerEntry::query()->whereBelongsTo($commissionLedger)->count())->toBe(2);

    $payout = AffiliatePayoutRequest::factory()
        ->for($affiliate)
        ->paid()
        ->create(['amount_baisa' => 2000]);

    $firstPayoutLedger = app(PostAffiliatePayoutLedgerTransaction::class)->execute($payout);
    $secondPayoutLedger = app(PostAffiliatePayoutLedgerTransaction::class)->execute($payout);
    $payoutEntries = $firstPayoutLedger->entries()->with('ledgerAccount')->get();

    expect($secondPayoutLedger->id)->toBe($firstPayoutLedger->id)
        ->and($payoutEntries)->toHaveCount(2)
        ->and($payoutEntries->sum('debit_baisa'))->toBe(2000)
        ->and($payoutEntries->sum('credit_baisa'))->toBe(2000)
        ->and($payoutEntries->firstWhere('debit_baisa', 2000)?->ledgerAccount->code)->toBe(LedgerAccount::AFFILIATE_COMMISSION_LIABILITY_CODE)
        ->and($payoutEntries->firstWhere('credit_baisa', 2000)?->ledgerAccount->code)->toBe(LedgerAccount::AFFILIATE_PAYOUT_CLEARING_CODE);
});

test('staff can manage affiliate statuses and payout requests in filament', function () {
    $staff = User::factory()->create();
    $pendingAffiliate = Affiliate::factory()->pending()->create();
    $approvedAffiliate = Affiliate::factory()->create();
    $suspendedAffiliate = Affiliate::factory()->suspended()->create();
    $payout = AffiliatePayoutRequest::factory()
        ->for($approvedAffiliate)
        ->create(['amount_baisa' => 20000]);

    $this->actingAs($staff, 'web');

    Livewire::test(ListAffiliates::class)
        ->assertCanSeeTableRecords([$pendingAffiliate, $approvedAffiliate, $suspendedAffiliate])
        ->callAction(TestAction::make('approve')->table($pendingAffiliate))
        ->assertHasNoActionErrors()
        ->assertNotified()
        ->callAction(TestAction::make('suspend')->table($approvedAffiliate))
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('reactivate')->table($suspendedAffiliate))
        ->assertHasNoActionErrors();

    expect($pendingAffiliate->refresh()->status)->toBe(AffiliateStatus::Approved)
        ->and($approvedAffiliate->refresh()->status)->toBe(AffiliateStatus::Suspended)
        ->and($suspendedAffiliate->refresh()->status)->toBe(AffiliateStatus::Approved);

    Livewire::test(ListAffiliatePayoutRequests::class)
        ->assertCanSeeTableRecords([$payout])
        ->callAction(TestAction::make('approve')->table($payout))
        ->assertHasNoActionErrors()
        ->assertNotified()
        ->callAction(TestAction::make('mark_paid')->table($payout->refresh()))
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($payout->refresh())
        ->status->toBe(AffiliatePayoutRequestStatus::Paid)
        ->paid_by_user_id->toBe($staff->id)
        ->and($payout->ledgerTransaction()->exists())->toBeTrue();
});

/**
 * @return array{0: Customer, 1: Event, 2: FamilyMember}
 */
function affiliateBookingInputFixture(): array
{
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12000,
        'minimum_age' => 9,
        'maximum_age' => 16,
        'seat_capacity' => 10,
        'starts_at' => now()->addMonth(),
    ]);
    $familyMember = FamilyMember::factory()
        ->for($customer)
        ->create(['birth_date' => now()->subYears(12)]);

    return [$customer, $event, $familyMember];
}

/**
 * @return array{0: Affiliate, 1: Payment, 2: Booking}
 */
function affiliatePaidPaymentFixture(
    int $amountBaisa,
    ?Affiliate $affiliate = null,
    int $bookingTotalBaisa = 0,
    PaymentState $paymentState = PaymentState::Paid,
): array {
    $affiliate ??= Affiliate::factory()->create();
    $bookingTotalBaisa = $bookingTotalBaisa > 0 ? $bookingTotalBaisa : $amountBaisa;
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'Affiliate Event']);
    $booking = Booking::factory()
        ->for($customer)
        ->for($event)
        ->create([
            'reference' => 'BRH-AFF-'.Str::upper(Str::random(8)),
            'currency' => 'OMR',
            'subtotal_baisa' => $bookingTotalBaisa,
            'total_baisa' => $bookingTotalBaisa,
        ]);

    AffiliateReferral::factory()
        ->for($affiliate)
        ->for($booking)
        ->create([
            'affiliate_code' => $affiliate->code,
            'affiliate_name' => $affiliate->name,
        ]);

    $schedule = BookingPaymentSchedule::factory()
        ->for($booking)
        ->create([
            'currency' => 'OMR',
            'subtotal_baisa' => $bookingTotalBaisa,
            'total_baisa' => $bookingTotalBaisa,
        ]);
    $installment = BookingInstallment::factory()
        ->for($schedule, 'paymentSchedule')
        ->create([
            'amount_baisa' => $amountBaisa,
            'state' => $paymentState === PaymentState::Paid ? BookingInstallmentState::Paid : BookingInstallmentState::Pending,
            'paid_at' => $paymentState === PaymentState::Paid ? now() : null,
        ]);
    $payment = Payment::factory()
        ->for($installment, 'bookingInstallment')
        ->create([
            'amount_baisa' => $amountBaisa,
            'currency' => 'OMR',
            'state' => $paymentState,
            'paid_at' => $paymentState === PaymentState::Paid ? now() : null,
            'provider_session_id' => 'checkout_affiliate_'.Str::lower(Str::random(8)),
        ]);

    return [$affiliate, $payment, $booking];
}
