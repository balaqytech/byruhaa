<?php

namespace App\Modules\Affiliates\Models;

use App\Enums\AffiliatePayoutRequestStatus;
use App\Enums\AffiliateStatus;
use App\Modules\Identity\Models\User;
use Database\Factories\AffiliateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string $phone_number
 * @property string $password
 * @property string $code
 * @property AffiliateStatus $status
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property string|null $review_notes
 */
#[Fillable(['name', 'email', 'phone_number', 'password', 'code', 'status', 'reviewed_by_user_id', 'reviewed_at', 'review_notes'])]
#[Hidden(['password', 'remember_token'])]
class Affiliate extends Authenticatable
{
    /** @use HasFactory<AffiliateFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory(): AffiliateFactory
    {
        return AffiliateFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Affiliate $affiliate): void {
            $affiliate->code ??= self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function isApproved(): bool
    {
        return $this->status === AffiliateStatus::Approved;
    }

    public function affiliateLink(): string
    {
        return route('events.index', ['ref' => $this->code]);
    }

    public function earnedCommissionBaisa(): int
    {
        $earned = (int) $this->commissions()->sum('commission_amount_baisa');
        $reversed = (int) AffiliateCommissionReversal::query()
            ->whereHas('commission', fn ($query) => $query->where('affiliate_id', $this->id))
            ->sum('amount_baisa');

        return $earned - $reversed;
    }

    public function requestedPayoutBaisa(): int
    {
        return (int) $this->payoutRequests()
            ->whereIn('status', [
                AffiliatePayoutRequestStatus::Pending->value,
                AffiliatePayoutRequestStatus::Approved->value,
                AffiliatePayoutRequestStatus::Paid->value,
            ])
            ->sum('amount_baisa');
    }

    public function availableBalanceBaisa(): int
    {
        return max(0, $this->earnedCommissionBaisa() - $this->requestedPayoutBaisa());
    }

    public function outstandingAdjustmentBaisa(): int
    {
        return max(0, $this->requestedPayoutBaisa() - $this->earnedCommissionBaisa());
    }

    public function pendingReferredBookingsCount(): int
    {
        return $this->referrals()->whereDoesntHave('commissions')->count();
    }

    public function paidReferredBookingsCount(): int
    {
        return $this->referrals()->whereHas('commissions')->count();
    }

    public function pendingPayoutBaisa(): int
    {
        return (int) $this->payoutRequests()
            ->whereIn('status', [
                AffiliatePayoutRequestStatus::Pending->value,
                AffiliatePayoutRequestStatus::Approved->value,
            ])
            ->sum('amount_baisa');
    }

    public function paidPayoutBaisa(): int
    {
        return (int) $this->payoutRequests()
            ->where('status', AffiliatePayoutRequestStatus::Paid->value)
            ->sum('amount_baisa');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @return HasMany<AffiliateReferral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    /**
     * @return HasMany<AffiliateCommission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * @return HasMany<AffiliatePayoutRequest, $this>
     */
    public function payoutRequests(): HasMany
    {
        return $this->hasMany(AffiliatePayoutRequest::class);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AffiliateStatus::class,
            'reviewed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
