<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Database\Factories\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string $event
 * @property string $webhook_url
 * @property string $webhook_url_hash
 * @property string $webhookable_type
 * @property int $webhookable_id
 * @property array<string, mixed> $payload
 * @property WebhookDeliveryStatus $status
 * @property int $attempts
 * @property Carbon|null $queued_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $final_failed_at
 * @property int|null $response_status
 * @property string|null $response_body
 * @property string|null $error_type
 * @property string|null $error_message
 */
#[Fillable(['uuid', 'event', 'webhook_url', 'webhook_url_hash', 'webhookable_type', 'webhookable_id', 'payload', 'status', 'attempts', 'queued_at', 'delivered_at', 'failed_at', 'final_failed_at', 'response_status', 'response_body', 'error_type', 'error_message'])]
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory;

    /**
     * @return MorphTo<Model, $this>
     */
    public function webhookable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookDeliveryStatus::class,
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'final_failed_at' => 'datetime',
            'response_status' => 'integer',
        ];
    }
}
