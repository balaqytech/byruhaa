<?php

namespace Database\Factories;

use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Models\EventPaymentPlanInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPaymentPlanInstallment>
 */
class EventPaymentPlanInstallmentFactory extends Factory
{
    protected $model = EventPaymentPlanInstallment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_payment_plan_id' => EventPaymentPlan::factory(),
            'name' => fake()->word(),
            'sequence' => 1,
            'percentage' => 100,
            'due_date' => now()->addWeek()->toDateString(),
        ];
    }
}
