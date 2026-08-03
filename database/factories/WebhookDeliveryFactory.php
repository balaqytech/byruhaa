<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Modules\Events\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = 'https://partner.test/webhook';

        return [
            'event' => 'booking.created',
            'webhook_url' => $url,
            'webhook_url_hash' => hash('sha256', $url),
            'webhookable_type' => Booking::class,
            'webhookable_id' => Booking::factory(),
            'payload' => ['event' => 'booking.created'],
            'status' => WebhookDeliveryStatus::Pending,
        ];
    }
}
