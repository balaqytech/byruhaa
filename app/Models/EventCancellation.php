<?php

namespace App\Models;

use App\Enums\EventCancellationStatus;
use Database\Factories\EventCancellationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int|null $cancelled_by_user_id
 * @property EventCancellationStatus $status
 * @property string $reason
 * @property string $currency
 * @property int $bookings_count
 * @property int $payments_count
 * @property int $refundable_amount_baisa
 * @property int $refunded_payments_count
 * @property int $refunded_amount_baisa
 * @property array<int, array<string, mixed>>|null $errors
 * @property Carbon $requested_at
 * @property Carbon|null $processing_started_at
 * @property Carbon|null $completed_at
 */
#[Fillable(['event_id', 'cancelled_by_user_id', 'status', 'reason', 'currency', 'bookings_count', 'payments_count', 'refundable_amount_baisa', 'refunded_payments_count', 'refunded_amount_baisa', 'errors', 'requested_at', 'processing_started_at', 'completed_at'])]
class EventCancellation extends Model
{
    /** @use HasFactory<EventCancellationFactory> */
    use HasFactory;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'status' => EventCancellationStatus::class,
            'errors' => 'array',
            'requested_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
