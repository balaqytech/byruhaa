<?php

namespace App\Enums;

enum PaymentRefundState: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Succeeded => __('admin.statuses.succeeded'),
            self::Failed => __('admin.statuses.failed'),
        };
    }
}
