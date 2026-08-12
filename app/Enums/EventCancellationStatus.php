<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventCancellationStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case NeedsAttention = 'needs_attention';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار المعالجة',
            self::Processing => 'جارٍ رد المبالغ',
            self::Completed => 'اكتمل الإلغاء',
            self::NeedsAttention => 'يحتاج متابعة',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::NeedsAttention => 'danger',
        };
    }
}
