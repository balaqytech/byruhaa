<?php

namespace App\Modules\Store\States\Order;

class Refunded extends OrderState
{
    public static string $name = 'refunded';

    public function getLabel(): string
    {
        return 'Refunded';
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
