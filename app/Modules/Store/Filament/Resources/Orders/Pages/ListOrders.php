<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Filament\Resources\Orders\OrderResource;
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
        ];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('admin.store.order_list_tabs.all')),
            'pending_payment' => Tab::make(__('admin.store.order_list_tabs.pending_payment'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending_payment')),
            'in_progress' => Tab::make(__('admin.store.order_list_tabs.in_progress'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', ['confirmed', 'accepted', 'preparing', 'ready_for_pickup'])),
            'completed' => Tab::make(__('admin.store.order_list_tabs.completed'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'completed')),
            'exceptions' => Tab::make(__('admin.store.order_list_tabs.exceptions'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', ['rejected', 'cancelled', 'expired', 'refund_pending', 'refunded'])),
        ];
    }
}
