<?php

namespace App\Notifications;

use App\Models\EventCancellation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $eventCancellationId)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $cancellation = EventCancellation::query()->with('event')->findOrFail($this->eventCancellationId);

        return [
            'type' => 'event_cancelled',
            'title' => 'تم إلغاء الفعالية',
            'message' => "تم إلغاء فعالية {$cancellation->event->name}. ستتم إعادة المدفوعات إلى وسيلة الدفع الأصلية.",
            'event_id' => $cancellation->event_id,
            'event_name' => $cancellation->event->name,
            'reason' => $cancellation->reason,
        ];
    }
}
