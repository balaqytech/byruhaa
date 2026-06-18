<?php

namespace App\States\Contract;

use App\Models\EventContract;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<EventContract>
 */
abstract class ContractState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(AwaitingSignature::class)
            ->allowTransition(AwaitingSignature::class, Signed::class)
            ->allowTransition(AwaitingSignature::class, Voided::class)
            ->allowTransition(Signed::class, Voided::class);
    }
}
