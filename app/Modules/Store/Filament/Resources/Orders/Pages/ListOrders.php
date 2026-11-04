<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('create_guest')
                ->label(__('admin.store.admin_order.create_guest_order'))
                ->url(OrderResource::getUrl('create-guest'))
                ->visible(fn (): bool => OrderResource::canCreate()),
        ];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make(__('admin.store.order_list_tabs.all')),
        ];

        foreach (OrderStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make(__('admin.store.order_statuses.'.$status->value))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status->value));
        }

        return $tabs;
    }
}
