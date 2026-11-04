<?php

namespace App\Modules\Store\States\Order;

class Cancelled extends OrderState
{
    public static string $name = 'cancelled';

    public function getLabel(): string
    {
        return __('Cancelled');
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
