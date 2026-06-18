<?php

namespace App\Enums;

enum UserRole: string
{
    case Staff = 'staff';
    case Admin = 'admin';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Staff->value => 'Staff',
            self::Admin->value => 'Admin',
        ];
    }
}
