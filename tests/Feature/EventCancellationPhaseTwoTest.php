<?php

use App\Actions\CancelEvent;
use App\Actions\ReverseAffiliateCommission;
use App\Enums\AffiliatePayoutRequestStatus;
use App\Enums\EventCancellationStatus;
use App\Enums\EventStatus;
use App\Jobs\ProcessEventCancellation;
use App\Models\EventCancellation;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use App\Modules\Affiliates\Models\AffiliateReferral;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\Event;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Customer;
use App\Notifications\EventCancelledNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;

test('event cancellation notifies each affected customer once and queues its webhook', function () {
    config(['byruhaa.webhooks.event_cancelled_url' => 'https://partner.test/event-cancelled']);
    Notification::fake();
    Queue::fake();
    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $customer = Customer::factory()->create();
    Booking::factory()->for($event)->for($customer)->count(2)->create();

    $first = app(CancelEvent::class)->execute($event, 'Weather conditions');
    app(CancelEvent::class)->execute($event, 'Ignored duplicate reason');

    Notification::assertSentToTimes($customer, EventCancelledNotification::class, 1);
    Queue::assertPushed(ProcessEventCancellation::class, 1);
    Queue::assertPushed(CallWebhookJob::class, fn (CallWebhookJob $job): bool => $job->webhookUrl === 'https://partner.test/event-cancelled'
        && $job->payload['event'] === 'event.cancelled'
        && $job->payload['data']['cancellation']['id'] === $first->id);
});

test('affiliate commission reversal is balanced idempotent and reduces affiliate balance', function () {
    $affiliate = Affiliate::factory()->create();
    $event = Event::factory()->create();
    $booking = Booking::factory()->for($event)->create();
    $payment = Payment::factory()->create();
    $referral = AffiliateReferral::factory()->for($affiliate)->for($booking)->create();
    $commission = AffiliateCommission::factory()->create([
        'affiliate_id' => $affiliate->id,
        'affiliate_referral_id' => $referral->id,
        'booking_id' => $booking->id,
        'payment_id' => $payment->id,
        'commission_amount_baisa' => 30000,
    ]);
    $cancellation = EventCancellation::factory()->for($event)->create([
        'status' => EventCancellationStatus::Processing,
    ]);
    $payoutRequest = AffiliatePayoutRequest::factory()->for($affiliate)->create([
        'amount_baisa' => 30000,
        'status' => AffiliatePayoutRequestStatus::Pending,
    ]);

    $first = app(ReverseAffiliateCommission::class)->execute($commission, $cancellation);
    $second = app(ReverseAffiliateCommission::class)->execute($commission, $cancellation);
    $entries = $first->ledgerTransaction()->firstOrFail()->entries;

    expect($first->id)->toBe($second->id)
        ->and($commission->reversal()->count())->toBe(1)
        ->and($entries->sum('debit_baisa'))->toBe(30000)
        ->and($entries->sum('credit_baisa'))->toBe(30000)
        ->and($affiliate->earnedCommissionBaisa())->toBe(0)
        ->and($affiliate->availableBalanceBaisa())->toBe(0)
        ->and($payoutRequest->refresh()->status)->toBe(AffiliatePayoutRequestStatus::Rejected);
});
