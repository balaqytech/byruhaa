<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\MinorProfileStatus;
use Database\Factories\MinorProfileFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property int $id
 * @property int $family_member_id
 * @property string $member_code
 * @property string|null $avatar_path
 * @property string $password
 * @property MinorProfileStatus $status
 * @property bool $direct_payment_enabled
 * @property bool $wallet_spending_enabled
 * @property Carbon|null $activated_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $invalidated_at
 * @property string|null $invalidation_reason
 * @property Carbon|null $deletion_requested_at
 * @property Carbon|null $activation_token_expires_at
 */
#[Fillable(['family_member_id', 'member_code', 'avatar_path', 'password', 'status', 'direct_payment_enabled', 'wallet_spending_enabled', 'activated_at', 'suspended_at', 'invalidated_at', 'invalidation_reason', 'deletion_requested_at', 'activation_token_hash', 'activation_token_expires_at'])]
#[Hidden(['password', 'remember_token', 'activation_token_hash'])]
class MinorProfile extends Authenticatable
{
    /** @use HasFactory<MinorProfileFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

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

    /** @return HasOne<MinorPosCredential, $this> */
    public function posCredential(): HasOne
    {
        return $this->hasOne(MinorPosCredential::class);
    }

    public function guardian(): Customer
    {
        return $this->familyMember->customer;
    }

    public function avatarUrl(?DateTimeInterface $expiresAt = null): ?string
    {
        return $this->avatar_path === null
            ? null
            : URL::temporarySignedRoute('minor-profiles.avatar', $expiresAt ?? now()->addDay(), [
                'minorProfile' => $this->id,
                'version' => hash('sha256', $this->avatar_path),
            ]);
    }

    public function isActive(): bool
    {
        return $this->status->isUsable();
    }

    public function allowsBrowserNotifications(): bool
    {
        return config('byruhaa.minor_accounts.browser_notifications.enabled', true)
            && $this->isActive()
            && $this->consents()->where('purpose', 'browser_notifications')->exists()
            && $this->pushSubscriptions()->exists();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => MinorProfileStatus::class,
            'password' => 'hashed',
            'direct_payment_enabled' => 'boolean',
            'wallet_spending_enabled' => 'boolean',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'activation_token_expires_at' => 'datetime',
        ];
    }
}
