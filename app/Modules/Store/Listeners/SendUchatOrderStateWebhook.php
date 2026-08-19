<?php

namespace App\Modules\Store\Listeners;

use App\Modules\Store\Events\OrderStateChanged;
use App\Services\Webhooks\ByruhaaWebhookSender;

class SendUchatOrderStateWebhook
{
    public function __construct(private ByruhaaWebhookSender $sender) {}

    public function handle(OrderStateChanged $event): void
    {
        $this->sender->sendUchatOrderState($event->order);
    }
}
