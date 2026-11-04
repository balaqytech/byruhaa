<?php

namespace App\Modules\Identity\Concerns;

use App\Modules\Identity\Contracts\AuditsIdentityRelations;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;

/**
 * @phpstan-require-implements AuditsIdentityRelations
 */
trait RecordsCustomAudits
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function recordCustomAudit(string $event, array $oldValues, array $newValues): void
    {
        $this->auditEvent = $event;
        $this->isCustomEvent = true;
        $this->auditCustomOld = $oldValues;
        $this->auditCustomNew = $newValues;

        try {
            Event::dispatch(new AuditCustom($this));
        } finally {
            $this->isCustomEvent = false;
            $this->auditCustomOld = null;
            $this->auditCustomNew = null;
        }
    }
}
