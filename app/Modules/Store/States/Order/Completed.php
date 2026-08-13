<?php

namespace App\Modules\Store\States\Order;

class Completed extends OrderState
{
    public static string $name = 'completed';

    public function getLabel(): string
    {
        return 'Completed';
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
