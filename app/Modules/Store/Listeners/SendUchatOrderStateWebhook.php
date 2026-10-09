<?php

namespace App\Modules\Store\Listeners;

use App\Modules\Store\Events\OrderStateChanged;
use App\Services\Webhooks\ByruhaaWebhookSender;

class SendUchatOrderStateWebhook
{
    public function __construct(private ByruhaaWebhookSender $sender) {}

    public function handle(OrderStateChanged $event): void
    {
        $order = $event->order->refresh();

        if ($order->pos_cashier_user_id !== null
            && in_array($order->status->getValue(), ['confirmed', 'cancelled', 'rejected', 'refunded'], true)) {
            return;
        }

        $this->sender->sendUchatOrderState($event->order);
    }
}
