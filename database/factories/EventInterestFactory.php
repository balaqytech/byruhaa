<?php

namespace Database\Factories;

use App\Enums\EventInterestSource;
use App\Enums\EventInterestStatus;
use App\Models\Event;
use App\Models\EventInterest;
use App\Modules\Identity\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventInterest>
 */
class EventInterestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(), 'event_id' => Event::factory(),
            'status' => EventInterestStatus::Interested, 'source' => EventInterestSource::Website,
            'preferred_contact_channel' => 'whatsapp', 'contact_consent_at' => now(),
            'last_expressed_at' => now(),
        ];
    }
}
