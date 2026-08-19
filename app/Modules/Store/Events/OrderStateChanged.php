<?php

namespace App\Modules\Store\Events;

use App\Modules\Store\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class OrderStateChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Order $order, public string $previousStatus) {}
}
