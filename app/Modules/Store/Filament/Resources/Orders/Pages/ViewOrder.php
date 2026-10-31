<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Actions\ManageOrderRefund;
use App\Modules\Store\Enums\OrderPickupType;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\OrderState;
use App\Modules\Store\States\Order\Preparing;
use App\Modules\Store\States\Order\Refunded;
use App\Modules\Store\States\Order\RefundPending;
use App\Support\Money\MoneyFactory;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('change_status')
                ->label(__('admin.store.actions.change_status'))
                ->visible(fn (Order $record): bool => OrderResource::canEdit($record))
                ->form([
                    Select::make('status')
                        ->options(fn (Order $record): array => collect($record->status->transitionableStateInstances())
                            ->reject(fn (OrderState $state): bool => in_array($state::class, [RefundPending::class, Refunded::class], true)
                                || ($record->pickup_type === OrderPickupType::Scheduled->value && $state::class === Preparing::class))
                            ->mapWithKeys(fn (OrderState $state): array => [$state::class => __('admin.store.order_statuses.'.$state->getValue())])
                            ->all())
                        ->required(),
                    Textarea::make('note')->label(__('admin.fields.note'))->maxLength(500),
                ])
                ->action(function (Order $record, array $data, ChangeOrderState $changeOrderState): void {
                    Gate::authorize('update', $record);
                    $target = (string) $data['status'];
                    if (! is_a($target, OrderState::class, true)) {
                        return;
                    }

                    $changeOrderState->execute($record, $target, is_numeric(auth()->id()) ? (int) auth()->id() : null, $data['note'] ?? null);
                    Notification::make()->title(__('admin.store.notifications.order_status_updated'))->success()->send();
                }),
            Action::make('request_refund')
                ->label('إنشاء طلب استرداد')
                ->visible(fn (Order $record): bool => OrderResource::canEdit($record) && $record->paid_at !== null)
                ->schema([
                    TextInput::make('amount_omr')->label('المبلغ بالريال')->required()->numeric()->minValue(0.001),
                    Textarea::make('reason')->label('السبب')->required()->maxLength(255),
                    Textarea::make('override_note')->label('سبب الاستثناء بعد 24 ساعة')->maxLength(500),
                ])
                ->action(function (Order $record, array $data, ManageOrderRefund $refunds): void {
                    Gate::authorize('update', $record);
                    $user = auth()->user();
                    if (! $user instanceof User) {
                        abort(403);
                    }
                    $completedAt = $record->statusHistory()->where('to_status', 'completed')->value('created_at');
                    $override = $completedAt !== null && Carbon::parse($completedAt)->addHours(24)->isPast();
                    if ($override && (! $user->isPanelAdministrator() || blank($data['override_note'] ?? null))) {
                        throw ValidationException::withMessages(['override_note' => 'يتطلب الاستثناء سببًا وصلاحية مدير النظام.']);
                    }
                    $refunds->request(
                        $record,
                        null,
                        $user->id,
                        MoneyFactory::omrStringToBaisa((string) $data['amount_omr']),
                        (string) $data['reason'].($override ? ' | '.(string) $data['override_note'] : ''),
                        (string) Str::uuid(),
                        [],
                        $override,
                    );
                    Notification::make()->title('تم تسجيل طلب الاسترداد للمراجعة')->success()->send();
                }),
        ];
    }
}
