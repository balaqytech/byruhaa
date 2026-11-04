<?php

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Events\WalletWithdrawalStateChanged;
use App\Services\Webhooks\ByruhaaWebhookSender;

class SendWalletWithdrawalNotification
{
    public function __construct(private ByruhaaWebhookSender $webhookSender) {}

    /**
     * Handle the event.
     */
    public function handle(WalletWithdrawalStateChanged $event): void
    {
        $this->webhookSender->sendUchatWalletWithdrawal($event->withdrawal);
    }
}
