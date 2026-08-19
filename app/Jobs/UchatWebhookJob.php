<?php

namespace App\Jobs;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Str;
use Spatie\WebhookServer\CallWebhookJob;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

/**
 * Sends UChat webhooks without retrying permanent HTTP client errors.
 */
class UchatWebhookJob extends CallWebhookJob
{
    public function handle(): void
    {
        $lastAttempt = $this->attempts() >= $this->tries;

        try {
            $body = strtoupper($this->httpVerb) === 'GET'
                ? ['query' => $this->payload]
                : ['body' => json_encode($this->payload)];

            if ($this->useTimestamp) {
                $this->addTimestampToHeaders();
            }

            $this->response = $this->createRequest($body);

            if (! Str::startsWith((string) $this->response->getStatusCode(), '2')) {
                throw new Exception('Webhook call failed');
            }

            $this->dispatchUchatEvent(WebhookCallSucceededEvent::class);
        } catch (Exception $exception) {
            if ($exception instanceof RequestException) {
                $response = $exception->getResponse();
                $this->response = $response instanceof GuzzleResponse ? $response : null;
                $this->errorType = $exception::class;
                $this->errorMessage = $exception->getMessage();
            }

            if ($exception instanceof ConnectException) {
                $this->errorType = $exception::class;
                $this->errorMessage = $exception->getMessage();
            }

            if ($this->response !== null && $this->errorMessage === null) {
                $this->errorType = $exception::class;
                $this->errorMessage = 'UChat webhook returned HTTP '.$this->response->getStatusCode().'.';
            }

            $retryable = $exception instanceof ConnectException
                || ($this->response !== null && ($this->response->getStatusCode() === 429 || $this->response->getStatusCode() >= 500));

            if (! $lastAttempt && $retryable) {
                $backoffStrategy = app($this->backoffStrategyClass);
                $this->release($backoffStrategy->waitInSecondsAfterAttempt($this->attempts()));
            }

            $this->dispatchUchatEvent(WebhookCallFailedEvent::class);

            if ($lastAttempt || ! $retryable) {
                $this->dispatchUchatEvent(FinalWebhookCallFailedEvent::class);

                if ($this->throwExceptionOnFailure) {
                    $this->fail($exception);
                } else {
                    $this->delete();
                }
            }
        }
    }

    private function dispatchUchatEvent(string $eventClass): void
    {
        event(new $eventClass(
            $this->httpVerb,
            $this->webhookUrl,
            $this->payload,
            $this->headers,
            $this->meta,
            $this->tags,
            $this->attempts(),
            $this->response,
            $this->errorType,
            $this->errorMessage,
            $this->uuid,
            $this->transferStats,
        ));
    }
}
