<?php

namespace App\Modules\Store\States\Order;

class Accepted extends OrderState
{
    public static string $name = 'accepted';

    public function getLabel(): string
    {
        return 'Accepted';
    }

    public function getColor(): string
    {
        return 'info';
    }
}
