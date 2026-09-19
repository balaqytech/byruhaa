<?php

namespace App\Modules\Identity\Enums;

enum MinorProfileStatus: string
{
    case PendingGuardianVerification = 'pending_guardian_verification';
    case PendingChildActivation = 'pending_child_activation';
    case Active = 'active';
    case Suspended = 'suspended';
    case Invalidated = 'invalidated';
    case DeletionRequested = 'deletion_requested';

    public function label(): string
    {
        return __('admin_minors.statuses.'.$this->value);
    }

    public function isUsable(): bool
    {
        return $this === self::Active;
    }
}
