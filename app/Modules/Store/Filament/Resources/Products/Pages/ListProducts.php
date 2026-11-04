<?php

namespace App\Modules\Store\Filament\Resources\Products\Pages;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('admin.store.product_list_tabs.all')),
            'active' => Tab::make(ProductStatus::Active->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ProductStatus::Active)),
            'draft' => Tab::make(ProductStatus::Draft->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ProductStatus::Draft)),
            'archived' => Tab::make(ProductStatus::Archived->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ProductStatus::Archived)),
            'featured' => Tab::make(__('admin.store.product_list_tabs.featured'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_featured', true)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
