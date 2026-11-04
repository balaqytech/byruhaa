<?php

use App\Actions\CancelEvent;
use App\Enums\EventStatus;
use App\Enums\PaymentState;
use App\Enums\WebhookDeliveryStatus;
use App\Jobs\ProcessEventCancellation;
use App\Jobs\UchatWebhookJob;
use App\Models\WebhookDelivery;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\Coupon;
use App\Modules\Events\Models\Event;
use App\Modules\Finance\Actions\RefundPayment;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Actions\ReviewCustomerDeletion;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\CustomerDeletionRequest;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\User;
use App\Services\Webhooks\ByruhaaWebhookSender;
use App\Services\Webhooks\UchatWebhookTransport;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    config([
        'byruhaa.uchat.api_token' => 'unified-test',
        'byruhaa.uchat.owner_key_secret' => 'owner-test',
        'byruhaa.uchat.webhook_url' => 'https://uchat.test/unified',
        'byruhaa.uchat.webhook_bearer_token' => 'outbound-test',
        'byruhaa.uchat.webhook_signing_secret' => 'signing-test',
        'byruhaa.approval_mechanism' => 'manual',
    ]);
    Queue::fake();
    $this->withHeaders(['Authorization' => 'Bearer unified-test', 'X-WhatsApp-Phone' => '91234567']);
});

test('unified identity requires bearer and a valid phone and rejects caller supplied customer IDs', function (): void {
    $this->withHeader('Authorization', 'Bearer wrong')->getJson('/api/v1/integrations/uchat/account')->assertUnauthorized();
    $this->withHeader('Authorization', 'Bearer unified-test')->withHeader('X-WhatsApp-Phone', '')->getJson('/api/v1/integrations/uchat/account')->assertUnprocessable();
    $this->withHeader('X-WhatsApp-Phone', '91234567')->getJson('/api/v1/integrations/uchat/account?customer_id=1')->assertUnprocessable();
});

test('account is resolved by normalized phone without requiring the store owner secret', function (): void {
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    config(['byruhaa.uchat.owner_key_secret' => null]);
    $this->getJson('/api/v1/integrations/uchat/account')->assertOk()->assertJsonPath('data.id', $customer->id);
    $this->patchJson('/api/v1/integrations/uchat/account', ['phone_number' => '+96892345678'])->assertUnprocessable();
});

test('registration uses the header phone and emits one webhook without exposing passwords', function (): void {
    $body = ['name' => 'Mona', 'password' => 'secret-password', 'password_confirmation' => 'secret-password'];
    $this->postJson('/api/v1/integrations/uchat/account', $body)->assertCreated()->assertJsonPath('data.phone_number', '+96891234567')->assertJsonMissingPath('data.password');
    expect(Hash::check('secret-password', Customer::query()->sole()->password))->toBeTrue();
    $this->postJson('/api/v1/integrations/uchat/account', $body)->assertConflict();
    expect(WebhookDelivery::query()->where('event', 'customer.registered')->count())->toBe(1);
});

test('family members and booking references cannot cross the phone identity', function (): void {
    Customer::factory()->create(['phone_number' => '+96891234567']);
    $other = Customer::factory()->create();
    $member = FamilyMember::factory()->for($other)->create();
    $booking = Booking::factory()->for($other)->create();
    $this->getJson('/api/v1/integrations/uchat/family-members/'.$member->id)->assertNotFound();
    $this->getJson('/api/v1/integrations/uchat/family-members/invalid-id')->assertNotFound();
    $this->getJson('/api/v1/integrations/uchat/bookings/'.$booking->reference)->assertNotFound();
});

test('booking creation is idempotent and replays after event cancellation', function (): void {
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    $member = FamilyMember::factory()->for($customer)->create(['birth_date' => now()->subYears(12)]);
    $event = Event::factory()->create(['price_baisa' => 15000]);
    $payload = ['event_id' => $event->id, 'family_member_ids' => [$member->id]];
    $this->postJson('/api/v1/integrations/uchat/bookings', $payload)->assertUnprocessable();
    $first = $this->withHeader('Idempotency-Key', 'booking-test')->postJson('/api/v1/integrations/uchat/bookings', $payload)->assertCreated()->assertJsonPath('data.total_baisa', 15000);
    $event->update(['status' => EventStatus::Cancelled]);
    $this->postJson('/api/v1/integrations/uchat/bookings', $payload)->assertCreated()->assertJsonPath('data.reference', $first->json('data.reference'));
    expect(Booking::query()->count())->toBe(1)->and(WebhookDelivery::query()->where('event', 'booking.created')->count())->toBe(1);
    $this->postJson('/api/v1/integrations/uchat/bookings', [...$payload, 'coupon_code' => 'DIFFERENT'])->assertConflict()->assertJsonPath('code', 'idempotency_conflict');
});

test('deletion requests are scoped repeatable and require authorized manual review', function (): void {
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    $first = $this->postJson('/api/v1/integrations/uchat/account/deletion-requests', ['reason' => 'Please review'])->assertAccepted();
    $this->postJson('/api/v1/integrations/uchat/account/deletion-requests')->assertAccepted()->assertJsonPath('data.id', $first->json('data.id'));
    expect(CustomerDeletionRequest::query()->count())->toBe(1)->and($customer->fresh())->not->toBeNull();
    $reviewer = User::factory()->create();
    $reviewer->syncRoles([]);
    expect(fn () => app(ReviewCustomerDeletion::class)->execute(CustomerDeletionRequest::query()->sole(), $reviewer, 'closed', 'Reviewed manually'))->toThrow(HttpException::class);
});

test('old sensitive routes and payment writes are removed', function (): void {
    $this->getJson('/api/v1/customers')->assertNotFound();
    $this->getJson('/api/v1/integrations/assistant/event-interests')->assertNotFound();
    $this->getJson('/api/v1/integrations/uchat/store/account')->assertNotFound();
    $this->postJson('/api/v1/integrations/uchat/payments', ['state' => 'paid'])->assertMethodNotAllowed();
});

test('cancelling an event emits per booking deliveries and a completion event exactly once in the audit', function (): void {
    Notification::fake();
    $event = Event::factory()->create();
    $one = Booking::factory()->for($event)->create();
    $two = Booking::factory()->for($event)->create();
    $cancellation = app(CancelEvent::class)->execute($event, 'Cancelled by staff');
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);
    expect(WebhookDelivery::query()->where('event', 'event.cancelled')->count())->toBe(1)
        ->and(WebhookDelivery::query()->where('event', 'booking.cancelled')->count())->toBe(2)
        ->and(WebhookDelivery::query()->where('event', 'event.cancellation.completed')->count())->toBe(1);
    $deliveries = WebhookDelivery::query()->where('event', 'booking.cancelled')->get();
    expect($deliveries->pluck('payload.customer_phone')->sort()->values()->all())->toBe(collect([$one->customer->phone_number, $two->customer->phone_number])->sort()->values()->all());
    expect($deliveries->first()->payload['data']['booking']['cancellation_id'])->toBe($cancellation->id);
});

test('webhook snapshots roll back and recovered deliveries retain their IDs and signatures', function (): void {
    $customer = Customer::factory()->create();
    DB::beginTransaction();
    app(ByruhaaWebhookSender::class)->sendCustomerRegistered($customer);
    DB::rollBack();
    expect(WebhookDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
    app(ByruhaaWebhookSender::class)->sendCustomerRegistered($customer);
    $delivery = WebhookDelivery::query()->sole();
    $delivery->update(['status' => WebhookDeliveryStatus::Pending]);
    app(UchatWebhookTransport::class)->dispatch($delivery->id);
    Queue::assertPushed(UchatWebhookJob::class, fn ($job): bool => $job->payload['delivery_id'] === $delivery->id && $job->payload['schema_version'] === 1 && $job->headers['Authorization'] === 'Bearer outbound-test');
    expect($delivery->fresh()->payload['delivery_id'])->toBe($delivery->id);
});

test('booking idempotency normalizes participants and prevents a second coupon redemption', function (): void {
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    $members = FamilyMember::factory()->count(2)->for($customer)->create(['birth_date' => now()->subYears(12)]);
    $event = Event::factory()->create(['price_baisa' => 15000]);
    $coupon = Coupon::factory()->for($event)->create(['code' => 'ONCE', 'maximum_uses' => 1, 'amount_baisa' => 1000]);
    $body = ['event_id' => $event->id, 'family_member_ids' => $members->pluck('id')->all(), 'coupon_code' => $coupon->code];
    $first = $this->withHeader('Idempotency-Key', 'coupon-replay')->postJson('/api/v1/integrations/uchat/bookings', $body)->assertCreated();
    $body['family_member_ids'] = array_reverse($body['family_member_ids']);
    $this->postJson('/api/v1/integrations/uchat/bookings', $body)->assertCreated()->assertJsonPath('data.reference', $first->json('data.reference'));
    expect(DB::table('coupon_redemptions')->where('coupon_id', $coupon->id)->count())->toBe(1);
    $other = Customer::factory()->create();
    $member = FamilyMember::factory()->for($other)->create(['birth_date' => now()->subYears(12)]);
    $this->withHeader('X-WhatsApp-Phone', $other->phone_number)->postJson('/api/v1/integrations/uchat/bookings', [...$body, 'family_member_ids' => [$member->id]])->assertConflict();
});

test('authorized review closes the request without removing any customer records', function (): void {
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    $this->postJson('/api/v1/integrations/uchat/account/deletion-requests')->assertAccepted();
    $request = CustomerDeletionRequest::query()->sole();
    $reviewer = User::factory()->admin()->create();
    app(ReviewCustomerDeletion::class)->execute($request, $reviewer, 'closed', 'Contacted customer and closed manual follow-up.');
    expect($request->fresh()->status)->toBe('closed')->and($request->fresh()->reviewed_by_user_id)->toBe($reviewer->id);
    $this->getJson('/api/v1/integrations/uchat/account')->assertOk();
    $this->assertModelExists($customer);
});

test('OTP delivery snapshots are encrypted and expired snapshots cannot be recovered', function (): void {
    $customer = Customer::factory()->create();
    app(ByruhaaWebhookSender::class)->sendUchatCustomerPhoneVerificationCode($customer, '123456', now()->addMinute(), 'otp-test');
    $delivery = WebhookDelivery::query()->sole();
    expect($delivery->payload['data']['verification']['code'])->toBe('[redacted]')
        ->and(DB::table('webhook_deliveries')->where('id', $delivery->id)->value('dispatch_payload'))->not->toContain('123456')
        ->and($delivery->dispatch_payload['data']['verification']['code'])->toBe('123456');
    $delivery->update(['status' => WebhookDeliveryStatus::Pending]);
    $this->travel(2)->minutes();
    expect(app(UchatWebhookTransport::class)->dispatch($delivery->id))->toBeFalse()
        ->and($delivery->fresh()->error_type)->toBe('verification_expired');
});

test('pending recovery is repeatable and retry preserves the signed snapshot', function (): void {
    $customer = Customer::factory()->create();
    app(ByruhaaWebhookSender::class)->sendCustomerRegistered($customer);
    $delivery = WebhookDelivery::query()->sole();
    $snapshot = $delivery->dispatch_payload;
    $delivery->forceFill(['status' => WebhookDeliveryStatus::Pending, 'created_at' => now()->subMinutes(2)])->save();
    Queue::fake();
    $this->artisan('uchat:webhooks:dispatch-pending')->assertSuccessful();
    $this->artisan('uchat:webhooks:dispatch-pending')->assertSuccessful();
    Queue::assertPushed(UchatWebhookJob::class, 1);
    Queue::assertPushed(UchatWebhookJob::class, fn ($job): bool => hash_equals(hash_hmac('sha256', json_encode($job->payload), 'signing-test'), $job->headers['Signature']));
    $delivery->update(['status' => WebhookDeliveryStatus::FinalFailed]);
    $this->artisan('uchat:webhooks:retry', ['ids' => [(string) $delivery->id]])->assertSuccessful();
    expect($delivery->fresh()->dispatch_payload)->toBe($snapshot);
});

test('webhook retries transient HTTP failures and stops permanent client errors', function (int $status): void {
    $customer = Customer::factory()->create();
    app(ByruhaaWebhookSender::class)->sendCustomerRegistered($customer);
    $queued = Queue::pushed(UchatWebhookJob::class)->first();
    $job = Mockery::mock(UchatWebhookJob::class)->makePartial()->shouldAllowMockingProtectedMethods();
    foreach (get_object_vars($queued) as $key => $value) {
        $job->{$key} = $value;
    }
    $job->throwExceptionOnFailure = false;
    $job->shouldReceive('attempts')->andReturn(1);
    $job->shouldReceive('createRequest')->once()->andReturn(new Response($status));
    if ($status === 400) {
        $job->shouldNotReceive('release');
        $job->shouldReceive('delete')->once();
    } else {
        $job->shouldReceive('release')->once();
    }
    $job->handle();
    expect(WebhookDelivery::query()->sole()->status)->toBe($status === 400 ? WebhookDeliveryStatus::FinalFailed : WebhookDeliveryStatus::Failed);
})->with([400, 429, 503]);

test('refund failure still cancels the booking and emits a needs attention outcome', function (): void {
    Notification::fake();
    $event = Event::factory()->create();
    $booking = Booking::factory()->for($event)->create();
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create();
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create();
    Payment::factory()->for($installment, 'bookingInstallment')->create(['state' => PaymentState::Paid, 'amount_baisa' => 1000]);
    $this->mock(RefundPayment::class)->shouldReceive('execute')->once()->andThrow(new RuntimeException('Refund provider unavailable'));
    $cancellation = app(CancelEvent::class)->execute($event, 'Staff cancellation');
    app()->call([new ProcessEventCancellation($cancellation->id), 'handle']);
    expect($booking->fresh()->state->getValue())->toBe('cancelled')
        ->and($cancellation->fresh()->status->value)->toBe('needs_attention')
        ->and(WebhookDelivery::query()->where('event', 'booking.cancelled')->count())->toBe(1)
        ->and(WebhookDelivery::query()->where('event', 'event.cancellation.needs_attention')->count())->toBe(1);
});
