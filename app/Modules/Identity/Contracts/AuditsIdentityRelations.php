<?php

namespace App\Modules\Identity\Contracts;

use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property string|null $auditEvent
 * @property bool $isCustomEvent
 * @property array<string, mixed>|null $auditCustomOld
 * @property array<string, mixed>|null $auditCustomNew
 */
interface AuditsIdentityRelations extends Auditable
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function recordCustomAudit(string $event, array $oldValues, array $newValues): void;
}
