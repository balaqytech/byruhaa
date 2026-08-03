<?php

namespace App\Modules\Events\States\Booking;

use App\Modules\Events\Models\Booking;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Booking>
 */
abstract class BookingState extends State implements HasColor, HasLabel
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
            ->default(PendingReview::class)
            ->allowTransition(PendingReview::class, Approved::class)
            ->allowTransition(PendingReview::class, Rejected::class)
            ->allowTransition(PendingReview::class, Cancelled::class)
            ->allowTransition(Approved::class, Cancelled::class);
    }
}
