<?php

namespace App\Listeners;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use Psr\Http\Message\ResponseInterface;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class UpdateWebhookDeliveryStatus
{
    private const RESPONSE_BODY_LIMIT = 10000;

    public function handle(WebhookCallEvent $event): void
    {
        $delivery = $this->delivery($event);

        if (! $delivery instanceof WebhookDelivery) {
            return;
        }

        $delivery->forceFill([
            'status' => $this->status($event),
            'attempts' => $event->attempt,
            'delivered_at' => $event instanceof WebhookCallSucceededEvent ? now() : $delivery->delivered_at,
            'failed_at' => $event instanceof WebhookCallFailedEvent ? now() : $delivery->failed_at,
            'final_failed_at' => $event instanceof FinalWebhookCallFailedEvent ? now() : $delivery->final_failed_at,
            'response_status' => $event->response?->getStatusCode(),
            'response_body' => $this->responseBody($event->response),
            'error_type' => $event->errorType,
            'error_message' => $this->truncate($event->errorMessage),
        ])->save();
    }

    private function delivery(WebhookCallEvent $event): ?WebhookDelivery
    {
        $deliveryId = data_get($event->meta, 'webhook_delivery_id');

        if (is_numeric($deliveryId)) {
            return WebhookDelivery::query()->find((int) $deliveryId);
        }

        if ($event->uuid !== '') {
            return WebhookDelivery::query()->where('uuid', $event->uuid)->first();
        }

        return null;
    }

    private function status(WebhookCallEvent $event): WebhookDeliveryStatus
    {
        return match (true) {
            $event instanceof WebhookCallSucceededEvent => WebhookDeliveryStatus::Delivered,
            $event instanceof FinalWebhookCallFailedEvent => WebhookDeliveryStatus::FinalFailed,
            default => WebhookDeliveryStatus::Failed,
        };
    }

    private function responseBody(?ResponseInterface $response): ?string
    {
        if (! $response instanceof ResponseInterface) {
            return null;
        }

        return $this->truncate((string) $response->getBody());
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str($value)->limit(self::RESPONSE_BODY_LIMIT, '')->toString();
    }
}
