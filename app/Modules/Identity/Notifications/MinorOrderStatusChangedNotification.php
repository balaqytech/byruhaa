<?php

namespace App\Modules\Identity\Notifications;

use App\Modules\Identity\Data\MinorOrderStatusData;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MinorOrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private MinorOrderStatusData $order)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof MinorProfile && $notifiable->allowsBrowserNotifications()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $message = $this->message();

        return [
            'type' => 'minor_order_status_changed',
            'reference' => $this->order->reference,
            'status' => $this->order->status,
            'status_label' => $this->order->statusLabel,
            'total_baisa' => $this->order->totalBaisa,
            'currency' => $this->order->currency,
            'message' => $message,
            'url' => $this->order->url,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('تحديث طلبك من بيرحاء')
            ->body($this->message())
            ->icon('/android-chrome-192x192.png')
            ->badge('/favicon-32x32.png')
            ->dir('rtl')
            ->lang('ar')
            ->tag('minor-order-'.hash('xxh3', $this->order->reference))
            ->data(['url' => $this->order->url])
            ->options(['TTL' => 3600, 'urgency' => 'normal', 'topic' => 'minor-order-'.hash('xxh3', $this->order->reference)]);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    private function message(): string
    {
        $total = number_format($this->order->totalBaisa / 1000, 3);

        return "الطلب {$this->order->reference}: {$this->order->statusLabel}. الإجمالي {$total} {$this->order->currency}.";
    }
}
