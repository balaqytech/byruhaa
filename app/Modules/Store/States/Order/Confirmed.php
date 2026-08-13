<?php

namespace App\Modules\Store\States\Order;

class Confirmed extends OrderState
{
    public static string $name = 'confirmed';

    public function getLabel(): string
    {
        return 'Confirmed';
    }

    public function getColor(): string
    {
        return 'success';
    }
}
