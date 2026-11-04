<?php

use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use App\Modules\Events\Models\Booking;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Models\Order;

test('new business references are short numeric strings and unique across types', function (): void {
    $order = Order::factory()->create();
    $booking = Booking::factory()->create();
    $payment = Payment::factory()->create();
    $refund = PaymentRefund::factory()->for($payment)->create();
    $payout = AffiliatePayoutRequest::factory()->create();
    $ledger = LedgerTransaction::factory()->create();
    $wallet = app(WalletService::class)->walletForMinorProfile(MinorProfile::factory()->create()->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'human-reference-test',
        'status' => 'pending',
        'amount_baisa' => 1000,
    ]);

    $references = [$order->reference, $booking->reference, $payment->reference, $refund->reference, $payout->reference, $ledger->reference, $topUp->reference];

    foreach ($references as $reference) {
        expect($reference)->toMatch('/^[0-9]{8}$/');
    }

    expect($references)->toHaveCount(count(array_unique($references)));
});

test('explicit legacy references remain unchanged', function (): void {
    $order = Order::factory()->create(['reference' => 'BRH-ORD-LEGACY']);
    $payment = Payment::factory()->create(['reference' => 'PAY-LEGACY']);

    expect($order->fresh()->reference)->toBe('BRH-ORD-LEGACY')
        ->and($payment->fresh()->reference)->toBe('PAY-LEGACY');
});
