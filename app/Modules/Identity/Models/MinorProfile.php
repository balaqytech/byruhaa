<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\MinorProfileStatus;
use Database\Factories\MinorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $family_member_id
 * @property string $member_code
 * @property string $password
 * @property MinorProfileStatus $status
 * @property bool $direct_payment_enabled
 * @property Carbon|null $activated_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $invalidated_at
 * @property string|null $invalidation_reason
 * @property Carbon|null $deletion_requested_at
 * @property Carbon|null $activation_token_expires_at
 */
#[Fillable(['family_member_id', 'member_code', 'password', 'status', 'direct_payment_enabled', 'activated_at', 'suspended_at', 'invalidated_at', 'invalidation_reason', 'deletion_requested_at', 'activation_token_hash', 'activation_token_expires_at'])]
#[Hidden(['password', 'remember_token', 'activation_token_hash'])]
class MinorProfile extends Authenticatable
{
    /** @use HasFactory<MinorProfileFactory> */
    use HasFactory, Notifiable;

    protected $table = 'minor_profiles';

    protected static function newFactory(): MinorProfileFactory
    {
        return MinorProfileFactory::new();
    }

    /**
     * @return BelongsTo<FamilyMember, $this>
     */
    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    /**
     * @return HasMany<MinorProfileVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(MinorProfileVerification::class);
    }

    /**
     * @return HasMany<MinorProfileConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(MinorProfileConsent::class);
    }

    public function guardian(): Customer
    {
        return $this->familyMember->customer;
    }

    public function isActive(): bool
    {
        return $this->status->isUsable();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => MinorProfileStatus::class,
            'password' => 'hashed',
            'direct_payment_enabled' => 'boolean',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'activation_token_expires_at' => 'datetime',
        ];
    }
}
