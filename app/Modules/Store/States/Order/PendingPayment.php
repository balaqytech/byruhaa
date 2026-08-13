<?php

namespace App\Modules\Store\States\Order;

class PendingPayment extends OrderState
{
    public static string $name = 'pending_payment';

    public function getLabel(): string
    {
        return 'Pending payment';
    }

    public function getColor(): string
    {
        return 'warning';
    }
}
