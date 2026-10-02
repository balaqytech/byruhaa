<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Enums\OrderStatus;
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
