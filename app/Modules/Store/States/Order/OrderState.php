<?php

namespace App\Modules\Store\States\Order;

use App\Modules\Store\Models\Order;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/** @extends State<Order> */
abstract class OrderState extends State implements HasColor, HasLabel
{
    abstract public function getLabel(): string;

    abstract public function getColor(): string;

    public function label(): string
    {
        return $this->getLabel();
    }

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(PendingPayment::class)
            ->allowTransition(PendingPayment::class, Confirmed::class)
            ->allowTransition(PendingPayment::class, Expired::class)
            ->allowTransition(Expired::class, RefundPending::class)
            ->allowTransition(Confirmed::class, Preparing::class)
            ->allowTransition(Confirmed::class, Accepted::class)
            ->allowTransition(Confirmed::class, Rejected::class)
            ->allowTransition(Accepted::class, Preparing::class)
            ->allowTransition(Preparing::class, ReadyForPickup::class)
            ->allowTransition(ReadyForPickup::class, Completed::class)
            ->allowTransition(Confirmed::class, Cancelled::class)
            ->allowTransition(Accepted::class, Cancelled::class)
            ->allowTransition(Preparing::class, Cancelled::class)
            ->allowTransition(Completed::class, RefundPending::class)
            ->allowTransition(RefundPending::class, Refunded::class);
    }
}
