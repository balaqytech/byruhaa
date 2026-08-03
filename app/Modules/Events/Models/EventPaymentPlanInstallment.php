<?php

namespace App\Modules\Events\Models;

use Database\Factories\EventPaymentPlanInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_payment_plan_id
 * @property string|null $name
 * @property int $sequence
 * @property int $percentage
 * @property Carbon $due_date
 */
#[Fillable(['event_payment_plan_id', 'name', 'sequence', 'percentage', 'due_date'])]
class EventPaymentPlanInstallment extends Model
{
    /** @use HasFactory<EventPaymentPlanInstallmentFactory> */
    use HasFactory;

    protected static function newFactory(): EventPaymentPlanInstallmentFactory
    {
        return EventPaymentPlanInstallmentFactory::new();
    }

    /**
     * @return BelongsTo<EventPaymentPlan, $this>
     */
    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(EventPaymentPlan::class, 'event_payment_plan_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'percentage' => 'integer',
            'due_date' => 'date',
        ];
    }
}
