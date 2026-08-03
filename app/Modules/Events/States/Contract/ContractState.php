<?php

namespace App\Modules\Events\States\Contract;

use App\Modules\Events\Models\EventContract;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<EventContract>
 */
abstract class ContractState extends State implements HasColor, HasLabel
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
            ->default(AwaitingSignature::class)
            ->allowTransition(AwaitingSignature::class, Signed::class)
            ->allowTransition(AwaitingSignature::class, Voided::class)
            ->allowTransition(Signed::class, Voided::class);
    }
}
