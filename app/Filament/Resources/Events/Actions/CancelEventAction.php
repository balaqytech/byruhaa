<?php

namespace App\Filament\Resources\Events\Actions;

use App\Actions\CancelEvent;
use App\Enums\EventCancellationStatus;
use App\Enums\EventStatus;
use App\Jobs\ProcessEventCancellation;
use App\Modules\Events\Models\Event;
use App\Modules\Finance\Models\Payment;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class CancelEventAction
{
    public static function make(): Action
    {
        return Action::make('cancel_event')
            ->label('إلغاء الفعالية ورد المبالغ')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Event $record): bool => $record->status !== EventStatus::Cancelled)
            ->modalHeading('تأكيد إلغاء الفعالية')
            ->modalDescription(fn (Event $record): string => self::impactSummary($record))
            ->schema([
                Textarea::make('reason')->label('سبب الإلغاء')->required()->minLength(5)->maxLength(1000),
            ])
            ->requiresConfirmation()
            ->action(function (Event $record, CancelEvent $cancelEvent, array $data): void {
                $userId = auth()->id();
                $cancelEvent->execute($record, (string) $data['reason'], is_numeric($userId) ? (int) $userId : null);
                Notification::make()->title('أُغلقت الفعالية وبدأت معالجة الحجوزات والمدفوعات')->success()->send();
            });
    }

    public static function retry(): Action
    {
        return Action::make('retry_event_cancellation')
            ->label('إعادة محاولة رد المبالغ')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->visible(fn (Event $record): bool => $record->cancellation?->status === EventCancellationStatus::NeedsAttention)
            ->requiresConfirmation()
            ->action(function (Event $record): void {
                ProcessEventCancellation::dispatch($record->cancellation()->firstOrFail()->id);
                Notification::make()->title('تمت جدولة إعادة المحاولة')->success()->send();
            });
    }

    private static function impactSummary(Event $event): string
    {
        $payments = Payment::query()
            ->whereHas('bookingInstallment.paymentSchedule.booking', fn ($query) => $query->where('event_id', $event->id))
            ->with('refunds')
            ->get();

        return sprintf(
            'سيُغلق التسجيل فورًا، وتُعالج %d حجوزات، ويُرد %s. لن تتحرر المقاعد المدفوعة قبل نجاح ردها.',
            $event->bookings()->count(),
            MoneyFormatter::baisa($payments->sum(fn (Payment $payment): int => $payment->refundableAmountBaisa()), $event->currency),
        );
    }
}
