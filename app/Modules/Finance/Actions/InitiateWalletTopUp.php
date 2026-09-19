<?php

namespace App\Modules\Finance\Actions;

use App\Enums\PaymentState;
use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
use App\Modules\Finance\Data\Payments\PaymentCheckoutRequest;
use App\Modules\Finance\Enums\WalletTopUpStatus;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\WalletTopUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitiateWalletTopUp
{
    public function __construct(
        private WalletService $wallets,
        private PaymentService $payments,
    ) {}

    public function execute(
        int $minorProfileId,
        string $operationKey,
        int $amountBaisa,
        string $successUrl,
        string $cancelUrl,
    ): PaymentCheckoutData {
        if ($amountBaisa < (int) config('byruhaa.wallets.minimum_top_up_baisa', 100)) {
            throw ValidationException::withMessages(['amount_baisa' => 'The wallet top-up amount is below the minimum.']);
        }

        if ($amountBaisa > (int) config('byruhaa.wallets.maximum_top_up_baisa', 100_000_000)) {
            throw ValidationException::withMessages(['amount_baisa' => 'The wallet top-up amount exceeds the maximum.']);
        }

        $wallet = $this->wallets->walletForMinorProfile($minorProfileId);
        $topUp = DB::transaction(function () use ($wallet, $operationKey, $amountBaisa): WalletTopUp {
            $existing = WalletTopUp::query()->where('operation_key', $operationKey)->lockForUpdate()->first();

            if ($existing instanceof WalletTopUp) {
                if ($existing->wallet_id !== $wallet->id || $existing->amount_baisa !== $amountBaisa) {
                    throw ValidationException::withMessages(['operation_key' => 'This operation key belongs to another top-up.']);
                }

                return $existing;
            }

            return WalletTopUp::query()->create([
                'wallet_id' => $wallet->id,
                'operation_key' => $operationKey,
                'status' => WalletTopUpStatus::Pending->value,
                'currency' => $wallet->currency,
                'amount_baisa' => $amountBaisa,
            ]);
        });

        if ($topUp->status !== WalletTopUpStatus::Pending->value) {
            throw ValidationException::withMessages(['top_up' => 'This top-up is no longer available for payment.']);
        }

        if ($topUp->payment_id !== null) {
            $existingPayment = Payment::query()->find($topUp->payment_id);

            if ($existingPayment?->state === PaymentState::Paid) {
                throw ValidationException::withMessages(['top_up' => 'This top-up has already been paid and is awaiting wallet credit.']);
            }
        }

        $checkout = $this->payments->initiate(new PaymentCheckoutRequest(
            'wallet_topup',
            $topUp->reference,
            $topUp->amount_baisa,
            $topUp->currency,
            [['name' => 'Wallet top-up', 'quantity' => 1, 'unit_amount' => $topUp->amount_baisa]],
            $successUrl,
            $cancelUrl,
            [
                'wallet_id' => $wallet->id,
                'minor_profile_id' => $minorProfileId,
                'top_up_reference' => $topUp->reference,
            ],
        ));

        $payment = Payment::query()->where('reference', $checkout->paymentReference)->firstOrFail();
        $topUp->forceFill(['payment_id' => $payment->id])->save();

        return $checkout;
    }
}
