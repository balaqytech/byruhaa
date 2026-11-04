<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $minor_profile_id
 * @property string $code_hash
 * @property Carbon $expires_at
 * @property int $attempts
 * @property Carbon|null $consumed_at
 */
#[Fillable(['minor_profile_id', 'code_hash', 'expires_at', 'attempts', 'consumed_at'])]
class MinorProfileVerification extends Model
{
    protected $table = 'minor_profile_verifications';

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
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
