<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventType: string implements HasColor, HasLabel
{
    case Trip = 'trip';
    case Camp = 'camp';
    case Festival = 'festival';

    public function getLabel(): string
    {
        return match ($this) {
            self::Trip => __('admin.event_types.trip'),
            self::Camp => __('admin.event_types.camp'),
            self::Festival => __('admin.event_types.festival'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Trip => 'info',
            self::Camp => 'success',
            self::Festival => 'warning',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
