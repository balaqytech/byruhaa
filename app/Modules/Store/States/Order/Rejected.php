<?php

namespace App\Modules\Store\States\Order;

class Rejected extends OrderState
{
    public static string $name = 'rejected';

    public function getLabel(): string
    {
        return 'Rejected';
    }

    public function getColor(): string
    {
        return 'danger';
    }
}
