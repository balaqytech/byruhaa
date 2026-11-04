<?php

namespace App\Modules\Store\Events;

use App\Modules\Store\Models\OrderRefundRequest;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderRefundStateChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public OrderRefundRequest $refund) {}
}
