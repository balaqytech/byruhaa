<?php

namespace App\Modules\Identity\Notifications;

use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MinorWalletTopUpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private int $amountBaisa,
        private int $balanceBaisa,
        private string $currency,
        private string $url,
    ) {
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
        return [
            'type' => 'minor_wallet_top_up',
            'amount_baisa' => $this->amountBaisa,
            'balance_baisa' => $this->balanceBaisa,
            'currency' => $this->currency,
            'message' => $this->message(),
            'url' => $this->url,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('تم شحن محفظتك')
            ->body($this->message())
            ->icon('/android-chrome-192x192.png')
            ->badge('/favicon-32x32.png')
            ->dir('rtl')
            ->lang('ar')
            ->tag('minor-wallet-top-up')
            ->data(['url' => $this->url])
            ->options(['TTL' => 3600, 'urgency' => 'normal', 'topic' => 'minor-wallet-top-up']);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    private function message(): string
    {
        $amount = number_format($this->amountBaisa / 1000, 3);
        $balance = number_format($this->balanceBaisa / 1000, 3);

        return "أضيف {$amount} {$this->currency} إلى محفظتك. رصيدك الآن {$balance} {$this->currency}.";
    }
}
