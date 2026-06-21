<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentProvider: string implements HasColor, HasLabel
{
    case Thawani = 'thawani';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Thawani => 'Thawani',
            self::Manual => 'Manual',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Thawani => 'info',
            self::Manual => 'gray',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
