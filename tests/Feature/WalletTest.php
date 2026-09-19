<?php

use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Jobs\UchatWebhookJob;
use App\Models\WebhookDelivery;
use App\Modules\Finance\Actions\RefundPayment;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Accepted;
use App\Modules\Store\States\Order\Cancelled;
use App\Modules\Store\States\Order\Completed;
use App\Modules\Store\States\Order\Confirmed;
use App\Modules\Store\States\Order\Preparing;
use App\Modules\Store\States\Order\ReadyForPickup;
use App\Modules\Store\States\Order\Refunded;
use App\Modules\Store\States\Order\RefundPending;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config([
        'byruhaa.wallets.enabled' => true,
        'byruhaa.wallets.top_up_refund_window_hours' => 24,
    ]);
});

test('a verified payment credits a child wallet exactly once', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-1',
        'status' => 'pending',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
    ]);
    $payment = Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-1',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'paid_at' => now(),
    ]);
    $topUp->forceFill(['payment_id' => $payment->id])->save();

    app(WalletService::class)->creditTopUp($payment);
    app(WalletService::class)->creditTopUp($payment->refresh());

    expect($wallet->refresh()->balance_baisa)->toBe(5000)
        ->and($topUp->refresh()->refundable_baisa)->toBe(5000)
        ->and($wallet->movements()->count())->toBe(1)
        ->and($payment->ledgerTransaction()->exists())->toBeTrue();
});

test('wallet purchases consume credited top ups oldest first and are idempotent', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);

    foreach ([3000, 4000] as $index => $amount) {
        $topUp = WalletTopUp::query()->create([
            'wallet_id' => $wallet->id,
            'operation_key' => 'top-up-'.($index + 1),
            'status' => 'credited',
            'currency' => 'OMR',
            'amount_baisa' => $amount,
            'refundable_baisa' => $amount,
            'spendable_baisa' => $amount,
            'credited_at' => now()->subMinutes(10 - $index),
            'refund_deadline_at' => now()->addDay(),
        ]);
        $wallet->increment('balance_baisa', $amount);
    }

    $first = $walletService->spend($profile->id, 'BRH-ORD-1', 5000, 'OMR');
    $second = $walletService->spend($profile->id, 'BRH-ORD-1', 5000, 'OMR');

    $topUps = WalletTopUp::query()->orderBy('id')->get();

    expect($first->movementId)->toBe($second->movementId)
        ->and($wallet->refresh()->balance_baisa)->toBe(2000)
        ->and($topUps[0]->refundable_baisa)->toBe(0)
        ->and($topUps[0]->spendable_baisa)->toBe(0)
        ->and($topUps[1]->refundable_baisa)->toBe(2000)
        ->and($topUps[1]->spendable_baisa)->toBe(2000)
        ->and($wallet->movements()->where('type', 'purchase')->count())->toBe(1);
});

test('an insufficient wallet purchase leaves the balance and allocations unchanged', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);

    expect(fn (): mixed => $walletService->spend($profile->id, 'BRH-ORD-2', 1000, 'OMR'))
        ->toThrow(ValidationException::class);

    expect($wallet->refresh()->balance_baisa)->toBe(0)
        ->and($wallet->movements()->count())->toBe(0)
        ->and($wallet->purchaseAllocations()->count())->toBe(0);
});

test('reversing a purchase after the refund window restores spendability', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-after-window',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'refundable_baisa' => 5000,
        'spendable_baisa' => 5000,
        'credited_at' => now()->subDay(),
        'refund_deadline_at' => now()->subMinute(),
    ]);
    $wallet->increment('balance_baisa', 5000);

    $walletService->spend($profile->id, 'BRH-ORD-AFTER-WINDOW', 3000, 'OMR');
    $walletService->reversePurchase('BRH-ORD-AFTER-WINDOW');

    expect($wallet->refresh()->balance_baisa)->toBe(5000)
        ->and($topUp->refresh()->spendable_baisa)->toBe(5000)
        ->and($topUp->refundable_baisa)->toBe(2000);
});

test('the Thawani reconciliation command recovers a paid uncredited wallet top up', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-recovery',
        'status' => 'pending',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
    ]);
    Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-RECOVERY',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'paid_at' => now(),
    ]);

    $this->artisan('payments:reconcile-thawani')->assertExitCode(0);

    expect($topUp->refresh()->status)->toBe('credited')
        ->and($wallet->refresh()->balance_baisa)->toBe(5000);
});

test('cancelling a wallet order reverses its wallet purchase', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-order-cancel',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'refundable_baisa' => 5000,
        'spendable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $wallet->increment('balance_baisa', $topUp->amount_baisa);
    $order = Order::factory()->create([
        'payment_method' => 'wallet',
        'minor_profile_id' => $profile->id,
        'customer_id' => $customer->id,
        'total_baisa' => 3000,
    ]);

    $walletService->spend($profile->id, $order->reference, 3000, 'OMR');
    app(ChangeOrderState::class)->execute($order, Confirmed::class);
    app(ChangeOrderState::class)->execute($order, Cancelled::class);

    expect($wallet->refresh()->balance_baisa)->toBe(5000)
        ->and($wallet->movements()->where('type', 'purchase_reversal')->count())->toBe(1)
        ->and($topUp->refresh()->spendable_baisa)->toBe(5000);
});

test('wallet top ups support partial refunds without a booking', function (): void {
    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => ['refund_id' => 'wallet-refund-1', 'status' => 'succeeded'],
        ]),
    ]);

    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-partial-refund',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'refundable_baisa' => 5000,
        'spendable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $wallet->increment('balance_baisa', 5000);
    $payment = Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-REFUND',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment-wallet-refund',
        'paid_at' => now(),
    ]);

    $refund = app(RefundPayment::class)->execute($payment, 3000, 'Wallet refund');

    expect($refund->state)->toBe(PaymentRefundState::Succeeded)
        ->and($payment->refresh()->state)->toBe(PaymentState::PartiallyRefunded)
        ->and($topUp->refresh()->refundable_baisa)->toBe(2000)
        ->and($topUp->spendable_baisa)->toBe(2000)
        ->and($topUp->reserved_refund_baisa)->toBe(0)
        ->and($wallet->refresh()->balance_baisa)->toBe(2000)
        ->and($wallet->movements()->where('type', 'top_up_refund')->count())->toBe(1)
        ->and($refund->ledgerTransaction()->exists())->toBeTrue();
});

test('wallet top-up refunds are rejected after the refund deadline', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-expired-refund',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'refundable_baisa' => 5000,
        'spendable_baisa' => 5000,
        'credited_at' => now()->subDay(),
        'refund_deadline_at' => now()->subSecond(),
    ]);
    $payment = Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-EXPIRED',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment-wallet-expired',
        'paid_at' => now()->subDay(),
    ]);

    expect(fn (): mixed => app(RefundPayment::class)->execute($payment, 1000))
        ->toThrow(ValidationException::class);

    expect($payment->refunds()->count())->toBe(0)
        ->and($topUp->refresh()->reserved_refund_baisa)->toBe(0);
});

test('uncertain refund failures retain the wallet refund reservation for manual resolution', function (): void {
    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
    Http::preventStrayRequests();
    $refundAttempts = 0;
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/refunds' => function () use (&$refundAttempts): never {
            $refundAttempts++;

            throw new ConnectionException('Connection timed out.');
        },
    ]);

    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-uncertain-refund',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'refundable_baisa' => 5000,
        'spendable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $payment = Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-UNCERTAIN',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment-wallet-uncertain',
        'paid_at' => now(),
    ]);

    expect(fn (): mixed => app(RefundPayment::class)->execute($payment, 2000))
        ->toThrow(ValidationException::class);

    $refund = $payment->refunds()->firstOrFail();
    expect($refund->state)->toBe(PaymentRefundState::ManualRequired)
        ->and($topUp->refresh()->status)->toBe('refunding')
        ->and($topUp->reserved_refund_baisa)->toBe(2000)
        ->and($payment->refresh()->refundableAmountBaisa())->toBe(3000)
        ->and($refundAttempts)->toBe(1);
});

test('spending can use the unreserved balance of a top up with a pending partial refund', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-pending-partial-refund',
        'status' => 'refunding',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'refundable_baisa' => 5000,
        'reserved_refund_baisa' => 3000,
        'spendable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $payment = Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-PENDING-REFUND',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment-wallet-pending-refund',
        'paid_at' => now(),
    ]);
    PaymentRefund::factory()->for($payment)->create([
        'amount_baisa' => 3000,
        'currency' => 'OMR',
        'state' => PaymentRefundState::Pending,
    ]);
    $wallet->increment('balance_baisa', 5000);

    $walletService->spend($profile->id, 'BRH-ORD-PARTIAL-REFUND', 1000, 'OMR');

    expect($wallet->refresh()->balance_baisa)->toBe(4000)
        ->and($topUp->refresh()->spendable_baisa)->toBe(4000)
        ->and($topUp->refundable_baisa)->toBe(4000)
        ->and($topUp->reserved_refund_baisa)->toBe(3000);
});

test('wallet movement notifications expose wallet history to UChat', function (): void {
    config([
        'byruhaa.uchat.webhook_url' => 'https://uchat.test/store',
        'byruhaa.uchat.webhook_bearer_token' => 'outbound-secret',
        'byruhaa.uchat.webhook_signing_secret' => 'signing-secret',
    ]);
    Queue::fake();
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-notification',
        'status' => 'pending',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
    ]);
    $payment = Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-NOTIFICATION',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'paid_at' => now(),
    ]);

    app(WalletService::class)->creditTopUp($payment);

    Queue::assertPushed(UchatWebhookJob::class);
    expect(WebhookDelivery::query()->where('event', 'wallet.top_up')->exists())->toBeTrue();
});

test('wallet reconciliation reports a balance discrepancy', function (): void {
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $wallet->forceFill(['balance_baisa' => 1])->save();

    $this->artisan('wallets:reconcile')
        ->assertExitCode(1);
});

test('wallet reconciliation reposts a credited top-up whose ledger posting failed', function (): void {
    config([
        'byruhaa.uchat.webhook_url' => 'https://uchat.test/store',
        'byruhaa.uchat.webhook_bearer_token' => 'outbound-secret',
        'byruhaa.uchat.webhook_signing_secret' => 'signing-secret',
    ]);
    Queue::fake();
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-missing-ledger',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'refundable_baisa' => 5000,
        'spendable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $wallet->increment('balance_baisa', 5000);
    $wallet->movements()->create([
        'wallet_top_up_id' => $topUp->id,
        'operation_key' => 'wallet-top-up:'.$topUp->id,
        'type' => 'top_up',
        'credit_baisa' => 5000,
        'debit_baisa' => 0,
        'balance_after_baisa' => 5000,
    ]);
    $payment = Payment::query()->create([
        'subject_type' => 'wallet_topup',
        'subject_reference' => $topUp->reference,
        'provider' => 'thawani',
        'reference' => 'PAY-WALLET-MISSING-LEDGER',
        'amount_baisa' => 5000,
        'currency' => 'OMR',
        'state' => PaymentState::Paid,
        'provider_payment_id' => 'payment-wallet-missing-ledger',
        'paid_at' => now(),
    ]);

    expect($payment->ledgerTransaction()->exists())->toBeFalse();

    $this->artisan('payments:reconcile-thawani')->assertExitCode(0);

    expect($payment->refresh()->ledgerTransaction()->exists())->toBeTrue()
        ->and(WebhookDelivery::query()->where('event', 'wallet.top_up')->exists())->toBeTrue();

    Queue::assertPushed(UchatWebhookJob::class);
});

test('wallet purchases become treasury eligible only after pickup completion', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-settlement',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 3000,
        'refundable_baisa' => 3000,
        'spendable_baisa' => 3000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $wallet->increment('balance_baisa', $topUp->amount_baisa);
    $order = Order::factory()->create([
        'payment_method' => 'wallet',
        'minor_profile_id' => $profile->id,
        'customer_id' => $customer->id,
        'total_baisa' => 3000,
    ]);

    $walletService->spend($profile->id, $order->reference, 3000, 'OMR');
    expect($wallet->settlements()->exists())->toBeFalse();

    app(ChangeOrderState::class)->execute($order, Confirmed::class);
    app(ChangeOrderState::class)->execute($order, Accepted::class);
    app(ChangeOrderState::class)->execute($order, Preparing::class);
    app(ChangeOrderState::class)->execute($order, ReadyForPickup::class);
    app(ChangeOrderState::class)->execute($order, Completed::class);

    $settlement = $wallet->settlements()->firstOrFail();

    expect($settlement->amount_baisa)->toBe(3000)
        ->and($settlement->status->value)->toBe('eligible')
        ->and($settlement->order_reference)->toBe($order->reference);
});

test('refunding a completed wallet order removes its treasury eligibility', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'top-up-reversed-settlement',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 3000,
        'refundable_baisa' => 3000,
        'spendable_baisa' => 3000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $wallet->increment('balance_baisa', $topUp->amount_baisa);
    $order = Order::factory()->create([
        'payment_method' => 'wallet',
        'minor_profile_id' => $profile->id,
        'customer_id' => $customer->id,
        'total_baisa' => 3000,
    ]);

    $walletService->spend($profile->id, $order->reference, 3000, 'OMR');
    app(ChangeOrderState::class)->execute($order, Confirmed::class);
    app(ChangeOrderState::class)->execute($order, Accepted::class);
    app(ChangeOrderState::class)->execute($order, Preparing::class);
    app(ChangeOrderState::class)->execute($order, ReadyForPickup::class);
    app(ChangeOrderState::class)->execute($order, Completed::class);
    app(ChangeOrderState::class)->execute($order, RefundPending::class);
    app(ChangeOrderState::class)->execute($order, Refunded::class);

    expect($wallet->settlements()->where('status', 'eligible')->exists())->toBeFalse()
        ->and($wallet->settlements()->sole()->status->value)->toBe('reversed');
});
