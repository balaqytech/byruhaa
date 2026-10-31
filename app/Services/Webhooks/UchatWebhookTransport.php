<?php

namespace App\Services\Webhooks;

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\UchatWebhookJob;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\WebhookServer\WebhookCall;
use Throwable;

class UchatWebhookTransport
{
    /** @return array{string, string, string}|null */
    public function configuration(): ?array
    {
        $url = trim((string) config('byruhaa.uchat.webhook_url'));
        $secret = trim((string) config('byruhaa.uchat.webhook_signing_secret'));
        $bearer = trim((string) config('byruhaa.uchat.webhook_bearer_token'));
        if ($url === '' && $secret === '' && $bearer === '') {
            return null;
        }
        if ($url === '' || $secret === '' || $bearer === '' || (in_array(config('app.env'), ['staging', 'production'], true) && ! str_starts_with(strtolower($url), 'https://'))) {
            Log::warning('UChat webhook configuration is incomplete or requires HTTPS.');

            return null;
        }

        return [$url, $secret, $bearer];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $auditPayload
     */
    public function record(Model $subject, array $payload, ?array $auditPayload = null, ?string $deliveryKey = null): void
    {
        $configuration = $this->configuration();
        if ($configuration === null) {
            return;
        }
        [$url] = $configuration;

        DB::transaction(function () use ($subject, $payload, $auditPayload, $deliveryKey, $url): void {
            $delivery = WebhookDelivery::query()->firstOrCreate([
                'event' => $payload['event'],
                'delivery_key' => $deliveryKey ?? '',
                'webhook_url_hash' => hash('sha256', $url),
                'webhookable_type' => $subject->getMorphClass(),
                'webhookable_id' => $subject->getKey(),
            ], ['uuid' => (string) Str::uuid(), 'webhook_url' => $url, 'payload' => [], 'status' => WebhookDeliveryStatus::Pending]);

            if (! $delivery->wasRecentlyCreated) {
                return;
            }
            $envelope = ['schema_version' => 1, 'delivery_id' => $delivery->id];
            $delivery->forceFill(['payload' => [...($auditPayload ?? $payload), ...$envelope], 'dispatch_payload' => [...$payload, ...$envelope]])->save();
            DB::afterCommit(fn () => $this->dispatch($delivery->id));
        });
    }

    public function dispatch(int $id, bool $retry = false): bool
    {
        $configuration = $this->configuration();
        if ($configuration === null) {
            return false;
        }
        [$url, $secret, $bearer] = $configuration;

        return DB::transaction(function () use ($id, $retry, $url, $secret, $bearer): bool {
            $delivery = WebhookDelivery::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $eligible = $delivery->status === WebhookDeliveryStatus::Pending
                || ($retry && in_array($delivery->status, [WebhookDeliveryStatus::Failed, WebhookDeliveryStatus::FinalFailed], true));
            if (! $eligible || $delivery->dispatch_payload === null || $delivery->webhook_url !== $url) {
                return false;
            }

            $expiresAt = data_get($delivery->dispatch_payload, 'data.verification.expires_at');
            if (is_string($expiresAt) && now()->gt(Carbon::parse($expiresAt))) {
                $delivery->forceFill(['status' => WebhookDeliveryStatus::FinalFailed, 'final_failed_at' => now(), 'error_type' => 'verification_expired'])->save();

                return false;
            }

            $defaultJob = config('webhook-server.webhook_job');
            Config::set('webhook-server.webhook_job', UchatWebhookJob::class);
            try {
                $call = WebhookCall::create();
            } finally {
                Config::set('webhook-server.webhook_job', $defaultJob);
            }

            try {
                $delivery->forceFill(['status' => WebhookDeliveryStatus::Queued, 'queued_at' => now(), 'final_failed_at' => null])->save();
                $call->uuid($delivery->uuid)->url($url)->payload($delivery->dispatch_payload)
                    ->withHeaders(['Authorization' => 'Bearer '.$bearer])->useSecret($secret)
                    ->onQueue(config('byruhaa.webhooks.queue', 'default'))
                    ->timeoutInSeconds((int) config('byruhaa.webhooks.timeout', 10))
                    ->meta(['webhook_delivery_id' => $delivery->id])->dispatch();

                return true;
            } catch (Throwable $exception) {
                $delivery->forceFill(['status' => WebhookDeliveryStatus::Pending, 'queued_at' => null, 'error_type' => $exception::class, 'error_message' => 'Webhook enqueue failed.'])->save();
                report($exception);

                return false;
            }
        });
    }
}
