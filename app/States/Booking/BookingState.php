<?php

namespace App\States\Booking;

use App\Models\Booking;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Booking>
 */
abstract class BookingState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(PendingReview::class)
            ->allowTransition(PendingReview::class, Approved::class)
            ->allowTransition(PendingReview::class, Rejected::class)
            ->allowTransition(PendingReview::class, Cancelled::class)
            ->allowTransition(Approved::class, Cancelled::class);
    }
}
