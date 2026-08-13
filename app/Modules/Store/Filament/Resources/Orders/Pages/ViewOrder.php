<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Enums\OrderPickupType;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\OrderState;
use App\Modules\Store\States\Order\Preparing;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('change_status')
                ->label('Change status')
                ->form([
                    Select::make('status')
                        ->options(fn (Order $record): array => collect($record->status->transitionableStateInstances())
                            ->reject(fn (OrderState $state): bool => $record->pickup_type === OrderPickupType::Scheduled->value && $state::class === Preparing::class)
                            ->mapWithKeys(fn (OrderState $state): array => [$state::class => $state->getLabel()])
                            ->all())
                        ->required(),
                    Textarea::make('note')->maxLength(500),
                ])
                ->action(function (Order $record, array $data, ChangeOrderState $changeOrderState): void {
                    $target = (string) $data['status'];
                    if (! is_a($target, OrderState::class, true)) {
                        return;
                    }

                    $changeOrderState->execute($record, $target, is_numeric(auth()->id()) ? (int) auth()->id() : null, $data['note'] ?? null);
                    Notification::make()->title('Order status updated')->success()->send();
                }),
        ];
    }
}
