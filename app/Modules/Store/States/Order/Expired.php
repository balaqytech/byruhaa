<?php

namespace App\Modules\Store\States\Order;

class Expired extends OrderState
{
    public static string $name = 'expired';

    public function getLabel(): string
    {
        return 'Expired';
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
