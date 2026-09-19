<?php

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Events\WalletMovementPosted;
use App\Services\Webhooks\ByruhaaWebhookSender;

class SendWalletMovementNotification
{
    public function __construct(private ByruhaaWebhookSender $webhookSender) {}

    public function handle(WalletMovementPosted $event): void
    {
        $this->webhookSender->sendUchatWalletMovement($event->movement);
    }
}
