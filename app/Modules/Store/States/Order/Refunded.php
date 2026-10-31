<?php

namespace App\Modules\Store\States\Order;

class Refunded extends OrderState
{
    public static string $name = 'refunded';

    public function getLabel(): string
    {
        return 'تم استرداد المبلغ';
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
