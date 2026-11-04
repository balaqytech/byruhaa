<?php

namespace App\Modules\Store\Actions;

use App\Enums\LedgerAccountType;
use App\Modules\Finance\Actions\PostFinancialEvent;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Store\Models\Order;
use Illuminate\Validation\ValidationException;

class PostStoreOrderPayment
{
    public function __construct(private PostFinancialEvent $postFinancialEvent) {}

    public function execute(Order $order): LedgerTransaction
    {
        if ($order->paid_at === null || $order->total_baisa !== $order->subtotal_baisa + $order->vat_baisa) {
            throw ValidationException::withMessages(['payment' => 'Store payment cannot be posted without a valid paid order.']);
        }

        [$debitCode, $debitName, $debitType] = match ($order->payment_method) {
            'cash' => [LedgerAccount::CASH_ON_HAND_CODE, 'Cash on hand', LedgerAccountType::Asset],
            'wallet' => [LedgerAccount::WALLET_LIABILITY_CODE, 'Wallet liability', LedgerAccountType::Liability],
            'thawani' => [LedgerAccount::THAWANI_CLEARING_CODE, 'Thawani clearing', LedgerAccountType::Asset],
            default => throw ValidationException::withMessages(['payment' => 'Unsupported store payment method.']),
        };

        $lines = [
            ['code' => $debitCode, 'name' => $debitName, 'type' => $debitType, 'debit' => $order->total_baisa, 'credit' => 0],
            ['code' => LedgerAccount::STORE_DEFERRED_CODE, 'name' => 'Store deferred revenue', 'type' => LedgerAccountType::Liability, 'debit' => 0, 'credit' => $order->subtotal_baisa],
        ];
        if ($order->vat_baisa > 0) {
            $lines[] = ['code' => LedgerAccount::VAT_PAYABLE_CODE, 'name' => 'VAT payable', 'type' => LedgerAccountType::Liability, 'debit' => 0, 'credit' => $order->vat_baisa];
        }

        return $this->postFinancialEvent->execute(
            'store-payment:'.$order->id,
            'store_payment',
            'store_order',
            $order->id,
            'Store payment '.$order->reference,
            $order->currency,
            $order->paid_at,
            $lines,
        );
    }
}
