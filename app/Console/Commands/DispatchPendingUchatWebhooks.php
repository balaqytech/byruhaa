<?php

namespace App\Console\Commands;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\UchatWebhookTransport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('uchat:webhooks:dispatch-pending')]
#[Description('Recover pending UChat webhook deliveries')]
class DispatchPendingUchatWebhooks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(UchatWebhookTransport $transport): int
    {
        WebhookDelivery::query()->where('status', WebhookDeliveryStatus::Pending)->whereNotNull('dispatch_payload')
            ->where('created_at', '<=', now()->subMinute())->select('id')->chunkById(100, function ($deliveries) use ($transport): void {
                foreach ($deliveries as $delivery) {
                    $transport->dispatch($delivery->id);
                }
            });

        return self::SUCCESS;
    }
}
