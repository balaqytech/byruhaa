<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $minor_profile_id
 * @property string $purpose
 * @property string $policy_version
 * @property string $policy_hash
 * @property Carbon $accepted_at
 * @property string|null $accepted_ip
 */
#[Fillable(['minor_profile_id', 'purpose', 'policy_version', 'policy_hash', 'accepted_at', 'accepted_ip'])]
class MinorProfileConsent extends Model
{
    protected $table = 'minor_profile_consents';

    /**
     * @return BelongsTo<MinorProfile, $this>
     */
    public function minorProfile(): BelongsTo
    {
        return $this->belongsTo(MinorProfile::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}
