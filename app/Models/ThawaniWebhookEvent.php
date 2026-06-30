<?php

namespace App\Models;

use App\Enums\ThawaniWebhookEventStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $payment_id
 * @property ThawaniWebhookEventStatus $status
 * @property string|null $event_type
 * @property string|null $provider_event_id
 * @property string|null $client_reference_id
 * @property string|null $provider_session_id
 * @property string|null $provider_payment_id
 * @property string|null $provider_invoice
 * @property string $payload_hash
 * @property array<string, mixed>|null $request_headers
 * @property array<string, mixed> $payload
 * @property int|null $response_status
 * @property string|null $error_message
 * @property Carbon|null $processed_at
 */
#[Fillable(['payment_id', 'status', 'event_type', 'provider_event_id', 'client_reference_id', 'provider_session_id', 'provider_payment_id', 'provider_invoice', 'payload_hash', 'request_headers', 'payload', 'response_status', 'error_message', 'processed_at'])]
class ThawaniWebhookEvent extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'received',
    ];

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ThawaniWebhookEventStatus::class,
            'request_headers' => 'array',
            'payload' => 'array',
            'response_status' => 'integer',
            'processed_at' => 'datetime',
        ];
    }
}
