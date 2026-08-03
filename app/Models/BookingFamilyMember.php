<?php

namespace App\Models;

use App\Modules\Identity\Models\FamilyMember;
use Database\Factories\BookingFamilyMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $family_member_id
 */
#[Fillable(['booking_id', 'family_member_id'])]
class BookingFamilyMember extends Model
{
    /** @use HasFactory<BookingFamilyMemberFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<FamilyMember, $this>
     */
    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    /**
     * @return HasOne<EventContract, $this>
     */
    public function contract(): HasOne
    {
        return $this->hasOne(EventContract::class)
            ->whereNull('superseded_at')
            ->latestOfMany();
    }

    /**
     * @return HasMany<EventContract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EventContract::class)->latest();
    }
}
