<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
