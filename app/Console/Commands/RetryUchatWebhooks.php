<?php

namespace App\Console\Commands;

use App\Models\WebhookDelivery;
use App\Services\Webhooks\UchatWebhookTransport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('uchat:webhooks:retry {ids* : Explicit delivery IDs to retry}')]
#[Description('Retry selected failed UChat webhook deliveries')]
class RetryUchatWebhooks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(UchatWebhookTransport $transport): int
    {
        foreach ($this->argument('ids') as $id) {
            if (! ctype_digit((string) $id) || ! WebhookDelivery::query()->whereKey($id)->exists()) {
                $this->error('Invalid delivery ID.');

                return self::FAILURE;
            }
        }
        foreach ($this->argument('ids') as $id) {
            $sent = $transport->dispatch((int) $id, retry: true);
            $this->line($id.': '.($sent ? 'queued' : 'not eligible'));
        }

        return self::SUCCESS;
    }
}
