<?php

namespace App\Modules\Identity\Models;

use App\Models\Booking;
use App\Models\EventInterest;
use App\Models\WebhookDelivery;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string $phone_number
 * @property string|null $civil_id
 * @property string|null $address
 * @property string|null $wilaya
 * @property string|null $area
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property array<string, mixed>|null $additional_info
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone_number', 'civil_id', 'address', 'wilaya', 'area', 'password', 'additional_info'])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    /**
     * @return array<int, string>
     */
    public static function requiredProfileFields(): array
    {
        return [
            'name',
            'phone_number',
            'civil_id',
            'address',
            'wilaya',
            'area',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function missingRequiredProfileFields(): array
    {
        return collect(self::requiredProfileFields())
            ->filter(fn (string $field): bool => blank($this->getAttribute($field)))
            ->values()
            ->all();
    }

    public function hasCompleteProfile(): bool
    {
        return $this->missingRequiredProfileFields() === [];
    }

    public function ensureProfileIsComplete(string $errorKey = 'profile'): void
    {
        if ($this->hasCompleteProfile()) {
            return;
        }

        throw ValidationException::withMessages([
            $errorKey => __('ui.messages.profile_incomplete'),
        ]);
    }

    /**
     * @return HasMany<FamilyMember, $this>
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<EventInterest, $this>
     */
    public function eventInterests(): HasMany
    {
        return $this->hasMany(EventInterest::class);
    }

    /**
     * @return MorphMany<WebhookDelivery, $this>
     */
    public function webhookDeliveries(): MorphMany
    {
        return $this->morphMany(WebhookDelivery::class, 'webhookable');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'additional_info' => 'array',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
