<?php

namespace App\Modules\Store\Actions;

use App\Enums\LedgerAccountType;
use App\Modules\Finance\Actions\PostFinancialEvent;
use App\Modules\Finance\Models\FinancialEvent;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use Illuminate\Validation\ValidationException;

class PostStoreOrderCompletion
{
    public function __construct(private PostFinancialEvent $postFinancialEvent) {}

    public function execute(Order $order): LedgerTransaction
    {
        $existing = FinancialEvent::query()->where('event_key', 'store-completion:'.$order->id)->first();
        if ($existing !== null) {
            return $existing->ledgerTransaction()->firstOrFail();
        }

        $refundedBeforeCompletion = (int) $order->refundRequests()->where('status', 'paid')->sum('subtotal_baisa');
        $recognizedBaisa = $order->subtotal_baisa - $refundedBeforeCompletion;
        if ($order->status->getValue() !== OrderStatus::Completed->value || $order->paid_at === null || $recognizedBaisa < 1) {
            throw ValidationException::withMessages(['order' => 'Only a paid completed order can recognize store revenue.']);
        }

        return $this->postFinancialEvent->execute(
            'store-completion:'.$order->id,
            'store_completion',
            'store_order',
            $order->id,
            'Store sale '.$order->reference,
            $order->currency,
            now(),
            [
                ['code' => LedgerAccount::STORE_DEFERRED_CODE, 'name' => 'Store deferred revenue', 'type' => LedgerAccountType::Liability, 'debit' => $recognizedBaisa, 'credit' => 0],
                ['code' => LedgerAccount::STORE_SALES_CODE, 'name' => 'Store sales', 'type' => LedgerAccountType::Income, 'debit' => 0, 'credit' => $recognizedBaisa],
            ],
        );
    }
}
