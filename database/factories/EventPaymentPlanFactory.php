<?php

namespace Database\Factories;

use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventPaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPaymentPlan>
 */
class EventPaymentPlanFactory extends Factory
{
    protected $model = EventPaymentPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->words(2, true),
            'is_active' => true,
        ];
    }
}
