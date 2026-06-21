<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Staff = 'staff';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Admin => 'Admin',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Staff => 'gray',
            self::Admin => 'primary',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->getLabel()])
            ->all();
    }
}
