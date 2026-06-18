<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $event_id
 * @property string $name
 * @property int $amount_baisa
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $minimum_family_members
 * @property int|null $maximum_family_members
 * @property bool $is_active
 */
#[Fillable(['event_id', 'name', 'amount_baisa', 'starts_at', 'ends_at', 'minimum_family_members', 'maximum_family_members', 'is_active'])]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @param  Builder<Discount>  $query
     * @return Builder<Discount>
     */
    public function scopeEligibleFor(Builder $query, Event $event, int $familyMemberCount, CarbonInterface $bookedAt): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('event_id')
                ->orWhere('event_id', $event->id))
            ->where(fn (Builder $query) => $query
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', $bookedAt))
            ->where(fn (Builder $query) => $query
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', $bookedAt))
            ->where(fn (Builder $query) => $query
                ->whereNull('minimum_family_members')
                ->orWhere('minimum_family_members', '<=', $familyMemberCount))
            ->where(fn (Builder $query) => $query
                ->whereNull('maximum_family_members')
                ->orWhere('maximum_family_members', '>=', $familyMemberCount));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_baisa' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'minimum_family_members' => 'integer',
            'maximum_family_members' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
