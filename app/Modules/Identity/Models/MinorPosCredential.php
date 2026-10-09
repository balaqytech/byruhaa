<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $minor_profile_id
 * @property string|null $token_hash
 * @property string|null $token_ciphertext
 * @property Carbon|null $card_issued_at
 * @property Carbon|null $card_revoked_at
 */
#[Fillable(['minor_profile_id', 'token_hash', 'token_ciphertext', 'card_issued_at', 'card_revoked_at'])]
#[Hidden(['token_hash', 'token_ciphertext'])]
class MinorPosCredential extends Model
{
    protected $table = 'minor_pos_credentials';

    /** @return BelongsTo<MinorProfile, $this> */
    public function minorProfile(): BelongsTo
    {
        return $this->belongsTo(MinorProfile::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'token_ciphertext' => 'encrypted',
            'card_issued_at' => 'datetime',
            'card_revoked_at' => 'datetime',
        ];
    }
}
