<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\FamilyMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $name
 * @property Carbon $birth_date
 * @property string|null $school_name
 * @property string|null $grade
 * @property string|null $medical_notes
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 */
#[Fillable(['customer_id', 'name', 'birth_date', 'school_name', 'grade', 'medical_notes', 'emergency_contact_name', 'emergency_contact_phone'])]
class FamilyMember extends Model
{
    /** @use HasFactory<FamilyMemberFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<BookingFamilyMember, $this>
     */
    public function bookingFamilyMembers(): HasMany
    {
        return $this->hasMany(BookingFamilyMember::class);
    }

    public function ageAt(CarbonInterface $date): int
    {
        return (int) $this->birth_date->diffInYears($date);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }
}
