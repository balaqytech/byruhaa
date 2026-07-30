<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventEnrollmentStatus: string implements HasColor, HasLabel
{
    case ComingSoon = 'coming_soon';
    case InterestOpen = 'interest_open';
    case BookingOpen = 'booking_open';
    case BookingClosed = 'booking_closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::ComingSoon => 'قريبًا', self::InterestOpen => 'استقبال المهتمين',
            self::BookingOpen => 'الحجز مفتوح', self::BookingClosed => 'الحجز مغلق',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ComingSoon => 'gray', self::InterestOpen => 'warning',
            self::BookingOpen => 'success', self::BookingClosed => 'danger',
        };
    }

    public function canExpressInterest(): bool
    {
        return $this === self::InterestOpen;
    }

    public function canBook(): bool
    {
        return $this === self::BookingOpen;
    }
}
