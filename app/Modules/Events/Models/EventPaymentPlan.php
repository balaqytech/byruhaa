<?php

namespace App\Modules\Events\Models;

use Database\Factories\EventPaymentPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property bool $is_active
 */
#[Fillable(['event_id', 'name', 'is_active'])]
class EventPaymentPlan extends Model
{
    /** @use HasFactory<EventPaymentPlanFactory> */
    use HasFactory;

    protected static function newFactory(): EventPaymentPlanFactory
    {
        return EventPaymentPlanFactory::new();
    }

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
     * @return HasMany<EventPaymentPlanInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(EventPaymentPlanInstallment::class)->orderBy('sequence');
    }

    public function percentageTotal(): int
    {
        return (int) $this->installments()->sum('percentage');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
