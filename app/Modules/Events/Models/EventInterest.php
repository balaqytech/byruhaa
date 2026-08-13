<?php

namespace App\Modules\Events\Models;

use App\Enums\EventInterestSource;
use App\Enums\EventInterestStatus;
use App\Models\WebhookDelivery;
use App\Modules\Identity\Models\Customer;
use Database\Factories\EventInterestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $event_id
 * @property int|null $booking_id
 * @property EventInterestStatus $status
 * @property EventInterestSource $source
 * @property string|null $preferred_contact_channel
 * @property string|null $source_reference
 * @property Carbon|null $contact_consent_at
 * @property Carbon $last_expressed_at
 * @property-read Customer $customer
 * @property-read Event $event
 */
#[Fillable(['customer_id', 'event_id', 'booking_id', 'status', 'source', 'preferred_contact_channel', 'source_reference', 'contact_consent_at', 'last_expressed_at', 'converted_at', 'withdrawn_at'])]
class EventInterest extends Model
{
    /** @use HasFactory<EventInterestFactory> */
    use HasFactory;

    protected static function newFactory(): EventInterestFactory
    {
        return EventInterestFactory::new();
    }

    protected $attributes = ['status' => 'interested', 'source' => 'website'];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return MorphMany<WebhookDelivery, $this> */
    public function webhookDeliveries(): MorphMany
    {
        return $this->morphMany(WebhookDelivery::class, 'webhookable');
    }

    protected function casts(): array
    {
        return [
            'status' => EventInterestStatus::class, 'source' => EventInterestSource::class,
            'contact_consent_at' => 'datetime', 'last_expressed_at' => 'datetime',
            'converted_at' => 'datetime', 'withdrawn_at' => 'datetime',
        ];
    }
}
