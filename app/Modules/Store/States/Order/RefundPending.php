<?php

namespace App\Modules\Store\States\Order;

class RefundPending extends OrderState
{
    public static string $name = 'refund_pending';

    public function getLabel(): string
    {
        return 'قيد الاسترداد';
    }

    public function getColor(): string
    {
        return 'warning';
    }
}
