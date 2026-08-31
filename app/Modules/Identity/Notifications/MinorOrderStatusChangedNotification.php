<?php

namespace App\Modules\Identity\Notifications;

use App\Modules\Identity\Data\MinorOrderStatusData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MinorOrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private MinorOrderStatusData $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'minor_order_status_changed',
            'reference' => $this->order->reference,
            'status' => $this->order->status,
            'status_label' => $this->order->statusLabel,
            'url' => $this->order->url,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
