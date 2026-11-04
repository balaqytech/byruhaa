<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Concerns\RecordsCustomAudits;
use App\Modules\Identity\Contracts\AuditsIdentityRelations;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole implements AuditableContract, AuditsIdentityRelations
{
    use AuditableTrait, RecordsCustomAudits;

    /**
     * @var array<int, string>
     */
    protected $auditInclude = [
        'name',
        'guard_name',
    ];
}
