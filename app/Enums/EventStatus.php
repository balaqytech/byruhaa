<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => __('admin.statuses.draft'),
            self::Published->value => __('admin.statuses.published'),
            self::Archived->value => __('admin.statuses.archived'),
        ];
    }
}
