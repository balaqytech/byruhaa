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

        if ($notifiable instanceof MinorProfile
            && config('byruhaa.minor_accounts.browser_notifications.enabled', true)
            && $notifiable->isActive()
            && $notifiable->consents()->where('purpose', 'browser_notifications')->exists()
            && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
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

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('بيرحاء')
            ->body('تم تحديث حالة طلبك. افتح حسابك للاطلاع على التفاصيل.')
            ->icon('/android-chrome-192x192.png')
            ->badge('/favicon-32x32.png')
            ->dir('rtl')
            ->lang('ar')
            ->tag('minor-order-status')
            ->data(['url' => $this->order->url])
            ->options(['TTL' => 3600, 'urgency' => 'normal', 'topic' => 'minor-order-status']);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }
}
