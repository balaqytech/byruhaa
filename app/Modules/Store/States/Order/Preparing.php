<?php

namespace App\Modules\Store\States\Order;

class Preparing extends OrderState
{
    public static string $name = 'preparing';

    public function getLabel(): string
    {
        return 'Preparing';
    }

    public function getColor(): string
    {
        return 'warning';
    }
}
