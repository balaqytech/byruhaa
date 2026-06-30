<?php

namespace App\Actions;

use App\Enums\ThawaniWebhookEventStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Models\ThawaniWebhookEvent;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class HandleThawaniWebhook
{
    public function __construct(
        private ResolveThawaniWebhookPayment $resolvePayment,
        private ConfirmThawaniPayment $confirmThawaniPayment,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, array<int, string>|string>  $headers
     */
    public function handle(array $payload, array $headers): ThawaniWebhookEvent
    {
        $identifiers = $this->resolvePayment->identifiers($payload);

        $event = ThawaniWebhookEvent::query()->create([
            'status' => ThawaniWebhookEventStatus::Received,
            'event_type' => $identifiers['event_type'],
            'provider_event_id' => $identifiers['provider_event_id'],
            'client_reference_id' => $identifiers['client_reference_id'],
            'provider_session_id' => $identifiers['provider_session_id'],
            'provider_payment_id' => $identifiers['provider_payment_id'],
            'provider_invoice' => $identifiers['provider_invoice'],
            'payload_hash' => $this->payloadHash($payload),
            'request_headers' => $this->redactedHeaders($headers),
            'payload' => $payload,
        ]);

        try {
            $payment = $this->resolvePayment->resolve($payload);

            if (! $payment instanceof Payment) {
                return $this->complete($event, ThawaniWebhookEventStatus::Unmatched, 202);
            }

            $event->forceFill(['payment_id' => $payment->id])->save();
            $this->confirmThawaniPayment->confirm($payment);

            return $this->complete($event, ThawaniWebhookEventStatus::Processed, 200);
        } catch (PaymentGatewayException $exception) {
            report($exception);

            return $this->complete($event, ThawaniWebhookEventStatus::Failed, 503, $exception->getMessage());
        } catch (RuntimeException $exception) {
            return $this->complete($event, ThawaniWebhookEventStatus::Rejected, 202, $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->complete($event, ThawaniWebhookEventStatus::Failed, 503, $exception->getMessage());
        }
    }

    private function complete(
        ThawaniWebhookEvent $event,
        ThawaniWebhookEventStatus $status,
        int $responseStatus,
        ?string $errorMessage = null,
    ): ThawaniWebhookEvent {
        $event->forceFill([
            'status' => $status,
            'response_status' => $responseStatus,
            'error_message' => $errorMessage ? str($errorMessage)->limit(10000, '')->toString() : null,
            'processed_at' => now(),
        ])->save();

        return $event->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, array<int, string>|string>  $headers
     * @return array<string, mixed>
     */
    private function redactedHeaders(array $headers): array
    {
        return collect($headers)
            ->mapWithKeys(function (array|string $value, string $key): array {
                $normalizedKey = Str::lower($key);

                if (Str::contains($normalizedKey, ['authorization', 'signature', 'secret', 'token', 'api-key', 'apikey'])) {
                    return [$key => ['[redacted]']];
                }

                return [$key => $value];
            })
            ->all();
    }
}
