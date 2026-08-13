<?php

namespace App\Modules\Store\States\Order;

class ReadyForPickup extends OrderState
{
    public static string $name = 'ready_for_pickup';

    public function getLabel(): string
    {
        return 'Ready for pickup';
    }

    public function getColor(): string
    {
        return 'success';
    }
}
