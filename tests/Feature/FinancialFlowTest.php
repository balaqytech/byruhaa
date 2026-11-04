<?php

use App\Enums\PaymentState;
use App\Filament\Resources\CashClosures\CashClosureResource;
use App\Filament\Resources\FinancialReconciliations\FinancialReconciliationResource;
use App\Filament\Resources\OrderRefunds\OrderRefundResource;
use App\Filament\Resources\ThawaniSettlements\ThawaniSettlementResource;
use App\Filament\Resources\WalletWithdrawals\WalletWithdrawalResource;
use App\Modules\Finance\Actions\CloseCashDrawer;
use App\Modules\Finance\Actions\ManageWalletWithdrawal;
use App\Modules\Finance\Actions\RecordThawaniSettlement;
use App\Modules\Finance\Actions\RunFinancialReconciliation;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Data\Payments\PaymentVerificationData;
use App\Modules\Finance\Models\FinancialEvent;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Actions\ConfirmStorePayment;
use App\Modules\Store\Actions\ManageOrderRefund;
use App\Modules\Store\Actions\PostStoreOrderCompletion;
use App\Modules\Store\Actions\PostStoreOrderPayment;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Accepted;
use App\Modules\Store\States\Order\Completed;
use App\Modules\Store\States\Order\Confirmed;
use App\Modules\Store\States\Order\Preparing;
use App\Modules\Store\States\Order\ReadyForPickup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function paidCashFinancialOrder(): Order
{
    $order = Order::factory()->create([
        'payment_method' => 'cash',
        'subtotal_baisa' => 1000,
        'vat_baisa' => 50,
        'total_baisa' => 1050,
        'cash_received_baisa' => 2000,
        'cash_change_baisa' => 950,
        'paid_at' => now(),
    ]);
    app(PostStoreOrderPayment::class)->execute($order);
    app(ChangeOrderState::class)->execute($order, Confirmed::class);

    return $order->refresh();
}

test('cash payment posts net cash and completion recognizes store revenue once', function (): void {
    $order = paidCashFinancialOrder();
    $payment = FinancialEvent::query()->where('event_key', 'store-payment:'.$order->id)->firstOrFail()->ledgerTransaction;
    expect($payment->entries()->whereHas('ledgerAccount', fn ($query) => $query->where('code', LedgerAccount::CASH_ON_HAND_CODE))->value('debit_baisa'))->toBe(1050)
        ->and($payment->entries()->sum('debit_baisa'))->toBe($payment->entries()->sum('credit_baisa'))
        ->and($payment->description)->toBe('قبض قيمة طلب القهوة '.$order->reference)
        ->and(LedgerAccount::query()->where('code', LedgerAccount::CASH_ON_HAND_CODE)->value('name'))->toBe('النقدية بالصندوق');

    foreach ([Accepted::class, Preparing::class, ReadyForPickup::class, Completed::class] as $state) {
        app(ChangeOrderState::class)->execute($order, $state);
    }
    app(PostStoreOrderCompletion::class)->execute($order->refresh());

    expect(FinancialEvent::query()->where('event_key', 'store-completion:'.$order->id)->count())->toBe(1);
    $completion = FinancialEvent::query()->where('event_key', 'store-completion:'.$order->id)->firstOrFail()->ledgerTransaction;
    expect($completion->entries()->whereHas('ledgerAccount', fn ($query) => $query->where('code', LedgerAccount::STORE_SALES_CODE))->value('credit_baisa'))->toBe(1000)
        ->and($completion->description)->toBe('إثبات إيراد طلب القهوة '.$order->reference);
});

test('repeated verified Store payment posts one Ledger event', function (): void {
    $order = Order::factory()->create(['payment_method' => 'thawani']);
    $verification = new PaymentVerificationData('store_order', $order->reference, 'PAYMENT-REPLAY', 1050, 'OMR', 'paid', 'SESSION-1', 'PROVIDER-1', null);
    $confirm = app(ConfirmStorePayment::class);

    $confirm->execute($verification);
    $confirm->execute($verification);

    expect($order->refresh()->status->getValue())->toBe('confirmed')
        ->and(FinancialEvent::query()->where('event_key', 'store-payment:'.$order->id)->count())->toBe(1);
});

test('cash refunds reserve remaining amount and post VAT reversal only after receipt', function (): void {
    $staff = User::factory()->admin()->create();
    $order = paidCashFinancialOrder();
    $refunds = app(ManageOrderRefund::class);
    $first = $refunds->request($order, null, $staff->id, 420, 'Returned items', 'cash-refund-1');
    $refunds->approve($first, $staff->id);
    expect($first->refresh()->status)->toBe('approved');
    $refunds->completeCash($first, $staff->id, 'CASH-RECEIPT-1');

    $second = $refunds->request($order, null, $staff->id, 630, 'Remaining amount', 'cash-refund-2');
    expect(fn (): mixed => $refunds->request($order, null, $staff->id, 1, 'Excess', 'cash-refund-3'))->toThrow(ValidationException::class);
    $refunds->approve($second, $staff->id);
    $refunds->completeCash($second, $staff->id, 'CASH-RECEIPT-2');

    expect($first->refresh()->status)->toBe('paid')
        ->and($order->refresh()->status->getValue())->toBe('refunded')
        ->and(FinancialEvent::query()->where('kind', 'store_refund')->count())->toBe(2)
        ->and($first->vat_baisa + $second->refresh()->vat_baisa)->toBe(50);
});

test('delivery recognizes only the net sale after a pre-completion partial refund', function (): void {
    $administrator = User::factory()->admin()->create();
    $order = paidCashFinancialOrder();
    $refunds = app(ManageOrderRefund::class);
    $refund = $refunds->request($order, null, $administrator->id, 420, 'Missing product', 'before-delivery-refund');
    $refunds->approve($refund, $administrator->id);
    $refunds->completeCash($refund, $administrator->id, 'RECEIPT-PARTIAL');
    foreach ([Accepted::class, Preparing::class, ReadyForPickup::class, Completed::class] as $state) {
        app(ChangeOrderState::class)->execute($order, $state);
    }

    $completion = FinancialEvent::query()->where('event_key', 'store-completion:'.$order->id)->firstOrFail()->ledgerTransaction;
    expect($completion->entries()->whereHas('ledgerAccount', fn ($query) => $query->where('code', LedgerAccount::STORE_SALES_CODE))->value('credit_baisa'))->toBe(600);
    app(PostStoreOrderCompletion::class)->execute($order->refresh());
    expect(FinancialEvent::query()->where('event_key', 'store-completion:'.$order->id)->count())->toBe(1);
});

test('partial Wallet order refund restores spendable balance and posts one reversal', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $administrator = User::factory()->admin()->create();
    $wallets = app(WalletService::class);
    $wallet = $wallets->walletForMinorProfile($profile->id);
    WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'wallet-refund-source',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 2000,
        'spendable_baisa' => 2000,
        'refundable_baisa' => 0,
        'credited_at' => now(),
    ]);
    $wallet->increment('balance_baisa', 2000);
    $order = Order::factory()->create(['customer_id' => $guardian->id, 'minor_profile_id' => $profile->id, 'payment_method' => 'wallet']);
    $wallets->spend($profile->id, $order->reference, $order->total_baisa, 'OMR');
    $order->forceFill(['paid_at' => now()])->save();
    app(PostStoreOrderPayment::class)->execute($order);
    app(ChangeOrderState::class)->execute($order, Confirmed::class);

    $refunds = app(ManageOrderRefund::class);
    $refund = $refunds->request($order, $guardian->id, null, 420, 'Wrong item', 'partial-wallet-refund');
    $refunds->approve($refund, $administrator->id);
    $wallets->refundPurchase($order->reference, $refund->reference, 420);
    app(PostStoreOrderPayment::class)->execute($order->refresh());

    expect($refund->refresh()->status)->toBe('paid')
        ->and($wallet->refresh()->balance_baisa)->toBe(1370)
        ->and($wallets->summary($profile->id)->availableBaisa)->toBe(1370)
        ->and(FinancialEvent::query()->where('event_key', 'store-refund:'.$refund->id)->count())->toBe(1);
});

test('withdrawal reserves funds and debits Wallet only after payout proof', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $staff = User::factory()->admin()->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'withdrawal-topup',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'spendable_baisa' => 5000,
        'refundable_baisa' => 5000,
        'credited_at' => now(),
    ]);
    $wallet->increment('balance_baisa', 5000);
    $withdrawals = app(ManageWalletWithdrawal::class);
    $request = $withdrawals->request($profile->id, $guardian->id, 3000, 'withdrawal-1', 'cash');

    expect($walletService->summary($profile->id)->availableBaisa)->toBe(2000)
        ->and($wallet->refresh()->balance_baisa)->toBe(5000)
        ->and($topUp->refresh()->reserved_withdrawal_baisa)->toBe(3000);
    expect(fn (): mixed => $walletService->spend($profile->id, 'OVERSPEND-ORDER', 2500, 'OMR'))->toThrow(ValidationException::class);
    expect(fn (): mixed => $withdrawals->completePayout($request, $staff->id, 'RECEIPT-1'))->toThrow(ValidationException::class);

    $withdrawals->approve($request, $staff->id);
    $withdrawals->startPayout($request, $staff->id);
    $withdrawals->completePayout($request, $staff->id, 'RECEIPT-1');

    expect($request->refresh()->status)->toBe('paid')
        ->and($wallet->refresh()->balance_baisa)->toBe(2000)
        ->and($topUp->refresh()->reserved_withdrawal_baisa)->toBe(0)
        ->and(FinancialEvent::query()->where('event_key', 'wallet-withdrawal:'.$request->id)->count())->toBe(1);
});

test('withdrawal enforces guardian ownership and bank proof, while rejection releases the hold', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $otherGuardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $administrator = User::factory()->admin()->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id, 'operation_key' => 'bank-withdrawal-topup', 'status' => 'credited',
        'currency' => 'OMR', 'amount_baisa' => 5000, 'spendable_baisa' => 5000, 'refundable_baisa' => 5000, 'credited_at' => now(),
    ]);
    $wallet->increment('balance_baisa', 5000);
    $withdrawals = app(ManageWalletWithdrawal::class);

    expect(fn (): mixed => $withdrawals->request($profile->id, $otherGuardian->id, 1000, 'wrong-guardian', 'cash'))
        ->toThrow(ValidationException::class);
    $rejected = $withdrawals->request($profile->id, $guardian->id, 1000, 'rejected-request', 'cash');
    $withdrawals->reject($rejected, $administrator->id, 'Cancelled after review');
    expect($topUp->refresh()->reserved_withdrawal_baisa)->toBe(0);

    $bankRequest = $withdrawals->request($profile->id, $guardian->id, 2000, 'bank-request', 'bank', 'OM12ACCOUNT', 'Bank A');
    expect($bankRequest->bank_beneficiary_name)->toBe($guardian->name);
    $withdrawals->approve($bankRequest, $administrator->id);
    $withdrawals->startPayout($bankRequest, $administrator->id);
    $withdrawals->markManualReview($bankRequest, $administrator->id, 'Bank confirmation pending');
    expect(fn (): mixed => $withdrawals->completePayout($bankRequest, $administrator->id, 'BANK-REF', 'proof.pdf'))
        ->toThrow(ValidationException::class);
    $withdrawals->completePayout($bankRequest, $administrator->id, 'BANK-REF', 'proof.pdf', true);
    expect($bankRequest->refresh()->status)->toBe('paid')
        ->and($wallet->refresh()->balance_baisa)->toBe(3000);
});

test('cash closure reports the net POS amount and a counted difference by cashier', function (): void {
    $cashier = User::factory()->create();
    $administrator = User::factory()->admin()->create();
    $order = paidCashFinancialOrder();
    $order->forceFill(['pos_cashier_user_id' => $cashier->id])->save();

    $closure = app(CloseCashDrawer::class)->execute($cashier->id, today()->toDateString(), 100, 1150, $administrator->id);
    expect($closure->receipts_baisa)->toBe(1050)
        ->and($closure->expected_baisa)->toBe(1150)
        ->and($closure->difference_baisa)->toBe(0)
        ->and($closure->status)->toBe('balanced');
});

test('Thawani settlement matches the original payment and posts Bank and fees', function (): void {
    $administrator = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'payment_method' => 'thawani',
        'subtotal_baisa' => 1000,
        'vat_baisa' => 50,
        'total_baisa' => 1050,
        'paid_at' => now(),
    ]);
    $payment = Payment::query()->create([
        'subject_type' => 'store_order',
        'subject_reference' => $order->reference,
        'provider' => 'thawani',
        'reference' => 'TEST-PAYMENT-SETTLEMENT',
        'provider_payment_id' => 'PROVIDER-PAYMENT-1',
        'amount_baisa' => 1050,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'paid_at' => now(),
    ]);
    app(PostStoreOrderPayment::class)->execute($order);

    $settlement = app(RecordThawaniSettlement::class)->execute('BATCH-1', [
        ['kind' => 'payment', 'reference' => $payment->provider_payment_id, 'amount_baisa' => 1050],
    ], 25, 1025, 'test-statement.csv', $administrator->id, Carbon::now());

    expect($settlement->items()->count())->toBe(1)
        ->and(FinancialEvent::query()->where('event_key', 'thawani-settlement:'.$settlement->id)->exists())->toBeTrue();
    $entries = FinancialEvent::query()->where('event_key', 'thawani-settlement:'.$settlement->id)->firstOrFail()->ledgerTransaction->entries;
    expect($entries->sum('debit_baisa'))->toBe(1050)->and($entries->sum('credit_baisa'))->toBe(1050);
});

test('financial reconciliation records unmatched Bank statement rows for accountant review', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('statement.csv', "reference,amount_baisa\nUNKNOWN-TRANSFER,1000\n");
    $administrator = User::factory()->admin()->create();

    $run = app(RunFinancialReconciliation::class)->execute(today()->toDateString(), $administrator->id, 'statement.csv');

    expect($run->status)->toBe('discrepancy')
        ->and($run->discrepancy_count)->toBe(1)
        ->and($run->issues[0]['reference'])->toBe('UNKNOWN-TRANSFER')
        ->and($run->issues[0]['owner'])->toBe('Accountant');
});

test('customer refund window closes after 24 hours but authorized staff may document an exception', function (): void {
    $customer = Customer::factory()->create();
    $administrator = User::factory()->admin()->create();
    $order = paidCashFinancialOrder();
    $order->forceFill(['customer_id' => $customer->id])->save();
    $history = $order->statusHistory()->create([
        'from_status' => 'ready_for_pickup',
        'to_status' => 'completed',
    ]);
    $history->forceFill(['created_at' => now()->subHours(25)])->save();
    $refunds = app(ManageOrderRefund::class);

    expect(fn (): mixed => $refunds->request($order, $customer->id, null, 100, 'Too late', 'late-customer'))
        ->toThrow(ValidationException::class);
    expect(fn (): mixed => $refunds->request($order, null, $administrator->id, 100, 'Guest request too late', 'late-guest'))
        ->toThrow(ValidationException::class);
    $approvedException = $refunds->request($order, $customer->id, $administrator->id, 100, 'Administrative exception: defective product', 'late-approved', [], true);
    expect($approvedException->admin_window_override)->toBeTrue();
});

test('paid refund issues an owned credit note linked to the original invoice', function (): void {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $administrator = User::factory()->admin()->create();
    $order = paidCashFinancialOrder();
    $order->forceFill(['customer_id' => $customer->id])->save();
    $refunds = app(ManageOrderRefund::class);
    $refund = $refunds->request($order, $customer->id, null, 1050, 'Wrong order', 'credit-note-test');
    $refunds->approve($refund, $administrator->id);
    $refunds->completeCash($refund, $administrator->id, 'CASH-CREDIT-1');

    $url = route('customer.store.orders.refunds.credit-note', [$order->payment_token, $refund]);
    $this->actingAs($customer, 'customer')->get($url)->assertOk()->assertSee($order->reference)->assertSee($refund->reference)->assertSee('إجمالي الاسترداد');
    $this->actingAs($otherCustomer, 'customer')->get($url)->assertNotFound();
});

test('uncertain Thawani refund remains in manual review until provider proof is recorded', function (): void {
    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
    Http::preventStrayRequests();
    Http::fake(['https://uatcheckout.thawani.om/api/v1/refunds' => Http::response(['success' => true, 'data' => ['refund_id' => 'PROVIDER-REFUND-1', 'status' => 'pending']])]);
    $administrator = User::factory()->admin()->create();
    $order = Order::factory()->create(['payment_method' => 'thawani', 'paid_at' => now(), 'payment_reference' => 'STORE-PAYMENT-1']);
    Payment::query()->create([
        'subject_type' => 'store_order', 'subject_reference' => $order->reference,
        'provider' => 'thawani', 'reference' => 'STORE-PAYMENT-1', 'amount_baisa' => $order->total_baisa,
        'currency' => 'OMR', 'provider_payment_id' => 'PROVIDER-PAYMENT-2', 'state' => PaymentState::Paid, 'paid_at' => now(),
    ]);
    app(PostStoreOrderPayment::class)->execute($order);
    app(ChangeOrderState::class)->execute($order, Confirmed::class);
    $refunds = app(ManageOrderRefund::class);
    $request = $refunds->request($order, null, $administrator->id, 300, 'Wrong item', 'thawani-refund-1');
    $refunds->approve($request, $administrator->id);

    expect($request->refresh()->status)->toBe('manual_review')
        ->and(FinancialEvent::query()->where('kind', 'store_refund')->count())->toBe(0);
    $refunds->confirmManualThawani($request, $administrator->id, 'PROVIDER-REFUND-1', 'provider-proof.pdf');
    expect($request->refresh()->status)->toBe('paid')
        ->and(FinancialEvent::query()->where('kind', 'store_refund')->count())->toBe(1);
});

test('finance review screens render for an authorized administrator', function (): void {
    $administrator = User::factory()->admin()->create();
    $this->actingAs($administrator, 'web');

    expect(OrderRefundResource::getPluralModelLabel())->toBe('طلبات استرداد القهوة');

    foreach ([
        OrderRefundResource::getUrl('index'),
        WalletWithdrawalResource::getUrl('index'),
        CashClosureResource::getUrl('index'),
        ThawaniSettlementResource::getUrl('index'),
        FinancialReconciliationResource::getUrl('index'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});
