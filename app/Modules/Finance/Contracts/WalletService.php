<?php

namespace App\Modules\Finance\Contracts;

use App\Modules\Finance\Data\WalletSpendData;
use App\Modules\Finance\Data\WalletSummaryData;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\Wallet;

interface WalletService
{
    public function walletForMinorProfile(int $minorProfileId, string $currency = 'OMR'): Wallet;

    public function summary(int $minorProfileId): WalletSummaryData;

    public function creditTopUp(Payment $payment, bool $retryNotification = false): void;

    public function spend(int $minorProfileId, string $orderReference, int $amountBaisa, string $currency): WalletSpendData;

    public function reversePurchase(string $orderReference): void;

    public function canClose(int $minorProfileId): bool;

    public function refundTopUp(PaymentRefund $refund): void;

    public function markPurchaseEligible(string $orderReference): void;
}
