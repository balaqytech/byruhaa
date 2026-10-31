<?php

namespace App\Modules\Store\Listeners;

use App\Modules\Store\Events\OrderRefundStateChanged;
use App\Services\Webhooks\ByruhaaWebhookSender;

class SendOrderRefundNotification
{
    public function __construct(private ByruhaaWebhookSender $webhookSender) {}

    /**
     * Handle the event.
     */
    public function handle(OrderRefundStateChanged $event): void
    {
        $this->webhookSender->sendUchatOrderRefund($event->refund);
    }
}
