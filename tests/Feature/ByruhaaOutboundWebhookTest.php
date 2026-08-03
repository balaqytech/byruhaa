<?php

use App\Actions\ConfirmThawaniPayment;
use App\Actions\CreateCustomerBooking;
use App\Actions\ExpressEventInterest;
use App\Enums\BookingInstallmentState;
use App\Enums\EventEnrollmentStatus;
use App\Enums\EventInterestSource;
use App\Enums\PaymentState;
use App\Enums\WebhookDeliveryStatus;
use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Event;
use App\Models\EventCancellation;
use App\Models\EventInterest;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\WebhookDelivery;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\User;
use App\Services\BookingApprovalService;
use App\Services\Webhooks\ByruhaaWebhookSender;
use App\States\Booking\Approved;
use App\States\Booking\Cancelled;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\WebhookServer\CallWebhookJob;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

beforeEach(function (): void {
    config([
        'app.url' => 'https://byruhaa.test',
        'byruhaa.webhooks.customer_registered_url' => null,
        'byruhaa.webhooks.interest_created_url' => null,
        'byruhaa.webhooks.booking_created_url' => null,
        'byruhaa.webhooks.booking_approved_url' => null,
        'byruhaa.webhooks.booking_cancelled_url' => null,
        'byruhaa.webhooks.booking_contracts_signed_url' => null,
        'byruhaa.webhooks.payment_paid_url' => null,
        'byruhaa.webhooks.event_cancelled_url' => null,
        'byruhaa.webhooks.payment_refunded_url' => null,
        'byruhaa.webhooks.signing_secret' => null,
        'byruhaa.webhooks.timeout' => 10,
        'byruhaa.webhooks.queue' => 'default',
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
});

test('byruhaa webhook config values exist', function () {
    expect(config('byruhaa.webhooks'))
        ->toHaveKeys([
            'customer_registered_url',
            'interest_created_url',
            'booking_created_url',
            'booking_approved_url',
            'booking_cancelled_url',
            'booking_contracts_signed_url',
            'payment_paid_url',
            'event_cancelled_url',
            'payment_refunded_url',
            'signing_secret',
            'timeout',
            'queue',
        ])
        ->and(config('byruhaa.webhooks.timeout'))->toBe(10)
        ->and(config('byruhaa.webhooks.queue'))->toBe('default');
});

test('cancelled booking queues one webhook with booking customer and event payload', function () {
    config(['byruhaa.webhooks.booking_cancelled_url' => 'https://partner.test/webhooks/booking-cancelled']);
    Queue::fake();
    $customer = Customer::factory()->create([
        'name' => 'Cancelled Customer',
        'phone_number' => '+96891234567',
        'email' => 'cancelled@example.com',
    ]);
    $event = Event::factory()->create(['name' => 'Cancelled Camp']);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'cancellation_reason' => 'The participant cannot attend.',
    ]);

    $booking->state->transitionTo(Cancelled::class);
    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/booking-cancelled');

    expect($job->payload)
        ->event->toBe('booking.cancelled')
        ->customer_phone->toBe('+96891234567')
        ->data->booking->id->toBe($booking->id)
        ->data->booking->status->toBe('cancelled')
        ->data->booking->reason->toBe('The participant cannot attend.')
        ->data->event->name->toBe('Cancelled Camp')
        ->data->customer->email->toBe('cancelled@example.com');

    app(ByruhaaWebhookSender::class)->sendBookingCancelled($booking->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
    expect(byruhaaWebhookDelivery('booking.cancelled', $booking, 'https://partner.test/webhooks/booking-cancelled')->status)
        ->toBe(WebhookDeliveryStatus::Queued);
});

test('new interest queues a webhook with interest event and customer payload', function () {
    config([
        'byruhaa.webhooks.interest_created_url' => 'https://partner.test/webhooks/interest-created',
        'byruhaa.webhooks.queue' => 'webhooks',
    ]);

    Queue::fake();

    $customer = Customer::factory()->create([
        'name' => 'Interested Customer',
        'phone_number' => '+96891234567',
        'email' => 'interested@example.com',
    ]);
    $event = Event::factory()->create([
        'name' => 'Interest Camp',
        'slug' => 'interest-camp',
        'enrollment_status' => EventEnrollmentStatus::InterestOpen,
    ]);

    $interest = app(ExpressEventInterest::class)->execute($customer, $event, [
        'preferred_contact_channel' => 'whatsapp',
        'source_reference' => 'assistant-conversation-42',
        'contact_consent' => true,
    ], EventInterestSource::Assistant);

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/interest-created');
    $payload = $job->payload;
    $delivery = byruhaaWebhookDelivery('interest.created', $interest, 'https://partner.test/webhooks/interest-created');

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Queued)
        ->and($delivery->queued_at)->not->toBeNull()
        ->and($job->queue)->toBe('webhooks')
        ->and($job->meta)->toHaveKey('webhook_delivery_id', $delivery->id)
        ->and($payload['event'])->toBe('interest.created')
        ->and($payload['customer_phone'])->toBe('+96891234567')
        ->and($payload['data']['interest']['id'])->toBe($interest->id)
        ->and($payload['data']['interest']['status'])->toBe('interested')
        ->and($payload['data']['interest']['source'])->toBe('assistant')
        ->and($payload['data']['interest']['preferred_contact_channel'])->toBe('whatsapp')
        ->and($payload['data']['interest']['source_reference'])->toBe('assistant-conversation-42')
        ->and($payload['data']['interest']['contact_consent_at'])->not->toBeNull()
        ->and($payload['data']['interest']['customer_panel_url'])->toBe(route('customer.interests.index'))
        ->and($payload['data']['event']['id'])->toBe($event->id)
        ->and($payload['data']['event']['name'])->toBe('Interest Camp')
        ->and($payload['data']['event']['slug'])->toBe('interest-camp')
        ->and($payload['data']['event']['public_url'])->toBe(route('events.show', $event))
        ->and($payload['data']['customer']['id'])->toBe($customer->id)
        ->and($payload['data']['customer']['phone'])->toBe('+96891234567')
        ->and($payload['data']['customer']['email'])->toBe('interested@example.com');
});

test('repeated interest expression queues only the newly created interest webhook', function () {
    config([
        'byruhaa.webhooks.interest_created_url' => 'https://partner.test/webhooks/interest-created',
    ]);

    Queue::fake();

    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::InterestOpen]);
    $expressInterest = app(ExpressEventInterest::class);

    $interest = $expressInterest->execute($customer, $event, [], EventInterestSource::Website);
    $expressInterest->execute($customer, $event, [], EventInterestSource::Assistant);
    app(ByruhaaWebhookSender::class)->sendInterestCreated($interest->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
    expect(WebhookDelivery::query()
        ->whereMorphedTo('webhookable', $interest)
        ->where('event', 'interest.created')
        ->count())->toBe(1);
});

test('new interest does not queue a webhook when its url is empty', function () {
    Queue::fake();

    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::InterestOpen]);
    $interest = app(ExpressEventInterest::class)->execute($customer, $event, [], EventInterestSource::Website);

    Queue::assertNotPushed(CallWebhookJob::class);
    expect($interest->webhookDeliveries()->count())->toBe(0);
});

test('legacy customer webhook morph types resolve after identity model move', function () {
    $customer = Customer::factory()->create();

    $delivery = WebhookDelivery::factory()->create([
        'webhookable_type' => 'App\\Models\\Customer',
        'webhookable_id' => $customer->getKey(),
    ]);

    expect($customer->getMorphClass())->toBe('App\\Models\\Customer')
        ->and($delivery->webhookable)->toBeInstanceOf(Customer::class);
});

test('customer registration through the api queues a webhook with safe customer payload', function () {
    config([
        'byruhaa.webhooks.customer_registered_url' => 'https://partner.test/webhooks/customer-registered',
        'byruhaa.webhooks.queue' => 'webhooks',
    ]);

    Queue::fake();

    $this->postJson('/api/v1/customers', [
        'name' => 'Registered Customer',
        'email' => 'registered@example.com',
        'phone_number' => '91234567',
        'civil_id' => '12345678',
        'address' => 'House 12',
        'wilaya' => 'Muscat',
        'area' => 'Qurum',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    $customer = Customer::query()->where('email', 'registered@example.com')->firstOrFail();
    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/customer-registered');
    $payload = $job->payload;
    $delivery = byruhaaWebhookDelivery('customer.registered', $customer, 'https://partner.test/webhooks/customer-registered');

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Queued)
        ->and($job->queue)->toBe('webhooks')
        ->and($job->meta)->toHaveKey('webhook_delivery_id', $delivery->id)
        ->and($payload['event'])->toBe('customer.registered')
        ->and($payload['customer_phone'])->toBe('+96891234567')
        ->and($payload['data']['customer']['id'])->toBe($customer->id)
        ->and($payload['data']['customer']['name'])->toBe('Registered Customer')
        ->and($payload['data']['customer']['phone'])->toBe('+96891234567')
        ->and($payload['data']['customer']['email'])->toBe('registered@example.com')
        ->and($payload['data']['customer']['civil_id'])->toBe('12345678')
        ->and($payload['data']['customer']['profile_complete'])->toBeTrue()
        ->and($payload['data']['customer']['customer_panel_url'])->toBe(route('customer.dashboard'));

    expect(json_encode($payload))
        ->not->toContain('password')
        ->not->toContain('remember_token');
});

test('customer registration through fortify queues a webhook', function () {
    config([
        'byruhaa.webhooks.customer_registered_url' => 'https://partner.test/webhooks/customer-registered',
    ]);

    Queue::fake();

    $this->post(route('register.store'), [
        'name' => 'Fortify Customer',
        'email' => 'fortify.webhook@example.com',
        'phone_number' => '92345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $customer = Customer::query()->where('email', 'fortify.webhook@example.com')->firstOrFail();
    $payload = byruhaaQueuedWebhook('https://partner.test/webhooks/customer-registered')->payload;

    expect($payload['event'])->toBe('customer.registered')
        ->and($payload['customer_phone'])->toBe('+96892345678')
        ->and($payload['data']['customer']['id'])->toBe($customer->id)
        ->and($payload['data']['customer']['phone'])->toBe('+96892345678')
        ->and($payload['data']['customer']['profile_complete'])->toBeFalse()
        ->and($payload['data']['customer']['missing_required_profile_fields'])->toBe(['civil_id', 'address', 'wilaya', 'area']);
});

test('customer registration does not queue a webhook when url is empty', function () {
    Queue::fake();

    $this->postJson('/api/v1/customers', [
        'name' => 'No Webhook Customer',
        'email' => 'no.webhook@example.com',
        'phone_number' => '91234568',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    $customer = Customer::query()->where('email', 'no.webhook@example.com')->firstOrFail();

    Queue::assertNotPushed(CallWebhookJob::class);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $customer)->count())->toBe(0);
});

test('repeated customer registered sender call does not queue duplicate webhooks', function () {
    config([
        'byruhaa.webhooks.customer_registered_url' => 'https://partner.test/webhooks/customer-registered',
    ]);

    Queue::fake();

    $customer = Customer::factory()->create();

    app(ByruhaaWebhookSender::class)->sendCustomerRegistered($customer);
    app(ByruhaaWebhookSender::class)->sendCustomerRegistered($customer->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $customer)->where('event', 'customer.registered')->count())->toBe(1);
});

test('booking creation queues a webhook with booking event customer and family member payload', function () {
    [$customer, $event, $familyMembers] = byruhaaBookingCreationFixture();

    config([
        'byruhaa.webhooks.booking_created_url' => 'https://partner.test/webhooks/booking-created',
        'byruhaa.webhooks.queue' => 'webhooks',
    ]);

    Queue::fake();

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/booking-created');
    $payload = $job->payload;

    $delivery = byruhaaWebhookDelivery('booking.created', $booking, 'https://partner.test/webhooks/booking-created');

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Queued)
        ->and($delivery->queued_at)->not->toBeNull()
        ->and($job->meta)->toHaveKey('webhook_delivery_id', $delivery->id)
        ->and($job->queue)->toBe('webhooks')
        ->and($job->headers)->not->toHaveKey('Signature')
        ->and($payload['event'])->toBe('booking.created')
        ->and($payload['customer_phone'])->toBe('+96892345678')
        ->and($payload['data']['booking']['id'])->toBe($booking->id)
        ->and($payload['data']['booking']['status'])->toBe('pending_review')
        ->and($payload['data']['booking']['customer_panel_url'])->toBe(route('customer.bookings.show', $booking))
        ->and($payload['data']['event']['id'])->toBe($event->id)
        ->and($payload['data']['event']['name'])->toBe('Created Booking Camp')
        ->and($payload['data']['customer']['id'])->toBe($customer->id)
        ->and($payload['data']['customer']['phone'])->toBe('+96892345678')
        ->and($payload['data']['participants'])->toHaveCount(2)
        ->and($payload['data']['participants'][0]['name'])->toBe('First Created Member')
        ->and($payload['data']['participants'][0]['contract_id'])->toBeNull()
        ->and($payload['data']['participants'][0]['contract_status'])->toBeNull()
        ->and($payload['data']['pricing']['unit_price'])->toBe('12.000')
        ->and($payload['data']['pricing']['subtotal'])->toBe('24.000')
        ->and($payload['data']['pricing']['total'])->toBe('24.000');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('booking creation does not queue a webhook when url is empty', function () {
    [$customer, $event, $familyMembers] = byruhaaBookingCreationFixture();

    Queue::fake();

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    Queue::assertNotPushed(CallWebhookJob::class);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $booking)->count())->toBe(0);
});

test('repeated booking created sender call does not queue duplicate webhooks', function () {
    [$customer, $event, $familyMembers] = byruhaaBookingCreationFixture();

    config([
        'byruhaa.webhooks.booking_created_url' => 'https://partner.test/webhooks/booking-created',
    ]);

    Queue::fake();

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    app(ByruhaaWebhookSender::class)->sendBookingCreated($booking->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $booking)->where('event', 'booking.created')->count())->toBe(1);
});

test('booking approval queues a signed webhook with booking payload', function () {
    [$booking, $staff] = byruhaaWebhookBookingFixture();

    config([
        'byruhaa.webhooks.booking_approved_url' => 'https://partner.test/webhooks/booking-approved',
        'byruhaa.webhooks.signing_secret' => 'secret',
        'byruhaa.webhooks.queue' => 'webhooks',
    ]);

    Queue::fake();

    $approvedBooking = app(BookingApprovalService::class)->approve($booking, $staff);

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/booking-approved');
    $payload = $job->payload;

    $delivery = byruhaaWebhookDelivery('booking.approved', $approvedBooking, 'https://partner.test/webhooks/booking-approved');

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Queued)
        ->and($delivery->queued_at)->not->toBeNull()
        ->and($job->meta)->toHaveKey('webhook_delivery_id', $delivery->id)
        ->and($job->queue)->toBe('webhooks')
        ->and($job->requestTimeout)->toBe(10)
        ->and($job->headers)->toHaveKey('Signature')
        ->and($payload['event'])->toBe('booking.approved')
        ->and($payload['customer_phone'])->toBe('+96891234567')
        ->and($payload['data']['booking']['id'])->toBe($approvedBooking->id)
        ->and($payload['data']['booking']['reference'])->toBe('BRH-WEBHOOK')
        ->and($payload['data']['booking']['status'])->toBe('approved')
        ->and($payload['data']['booking']['customer_panel_url'])->toBe(route('customer.bookings.show', $approvedBooking))
        ->and($payload['data']['customer']['phone'])->toBe('+96891234567')
        ->and($payload['data']['event']['name'])->toBe('Webhook Camp')
        ->and($payload['data']['participants'][0]['name'])->toBe('Maha Webhook')
        ->and($payload['data']['participants'][0]['contract_status'])->toBe('awaiting_signature')
        ->and($payload['data']['pricing']['unit_price'])->toBe('489.000')
        ->and($payload['data']['pricing']['total'])->toBe('489.000');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('booking approval does not queue a webhook when url is empty', function () {
    [$booking, $staff] = byruhaaWebhookBookingFixture();

    Queue::fake();

    $approvedBooking = app(BookingApprovalService::class)->approve($booking, $staff);

    Queue::assertNotPushed(CallWebhookJob::class);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $approvedBooking)->count())->toBe(0);
});

test('signing all booking contracts queues a webhook with contract summary', function () {
    [$booking, $staff] = byruhaaWebhookBookingFixture(familyMemberCount: 2);

    config([
        'byruhaa.webhooks.booking_contracts_signed_url' => 'https://partner.test/webhooks/booking-contracts-signed',
        'byruhaa.webhooks.queue' => 'webhooks',
    ]);

    $approvedBooking = app(BookingApprovalService::class)->approve($booking, $staff)
        ->load('familyMembers.contract');
    $contracts = $approvedBooking->familyMembers->pluck('contract');

    Storage::fake('local');
    Queue::fake();

    $contracts[0]->sign(byruhaaSignatureDataUrl(), 'First signer', '127.0.0.1');

    Queue::assertNotPushed(CallWebhookJob::class);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $approvedBooking)->where('event', 'booking.contracts_signed')->count())->toBe(0);

    $contracts[1]->sign(byruhaaSignatureDataUrl(), 'Second signer', '127.0.0.1');

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/booking-contracts-signed');
    $payload = $job->payload;
    $delivery = byruhaaWebhookDelivery('booking.contracts_signed', $approvedBooking, 'https://partner.test/webhooks/booking-contracts-signed');

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Queued)
        ->and($job->queue)->toBe('webhooks')
        ->and($payload['event'])->toBe('booking.contracts_signed')
        ->and($payload['customer_phone'])->toBe('+96891234567')
        ->and($payload['data']['booking']['id'])->toBe($approvedBooking->id)
        ->and($payload['data']['booking']['status'])->toBe('approved')
        ->and($payload['data']['booking']['customer_panel_url'])->toBe(route('customer.bookings.show', $approvedBooking))
        ->and($payload['data']['event']['name'])->toBe('Webhook Camp')
        ->and($payload['data']['customer']['phone'])->toBe('+96891234567')
        ->and($payload['data']['participants'])->toHaveCount(2)
        ->and($payload['data']['participants'][0]['contract_status'])->toBe('signed')
        ->and($payload['data']['participants'][0]['contract_signed_at'])->not->toBeNull()
        ->and($payload['data']['participants'][1]['contract_status'])->toBe('signed')
        ->and($payload['data']['contracts']['total_count'])->toBe(2)
        ->and($payload['data']['contracts']['signed_count'])->toBe(2)
        ->and($payload['data']['contracts']['all_signed'])->toBeTrue()
        ->and($payload['data']['contracts']['latest_signed_at'])->not->toBeNull()
        ->and($payload['data']['pricing']['total'])->toBe('978.000');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('all contracts signed webhook is idempotent', function () {
    [$booking, $staff] = byruhaaWebhookBookingFixture();

    config([
        'byruhaa.webhooks.booking_contracts_signed_url' => 'https://partner.test/webhooks/booking-contracts-signed',
    ]);

    $approvedBooking = app(BookingApprovalService::class)->approve($booking, $staff)
        ->load('familyMembers.contract');

    Storage::fake('local');
    Queue::fake();

    $approvedBooking->familyMembers->first()->contract->sign(byruhaaSignatureDataUrl(), 'Only signer', '127.0.0.1');
    app(ByruhaaWebhookSender::class)->sendBookingContractsSigned($approvedBooking->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $approvedBooking)->where('event', 'booking.contracts_signed')->count())->toBe(1);
});

test('paid thawani confirmation queues a payment webhook with paid booking status', function () {
    $payment = byruhaaWebhookPaymentFixture(amountBaisa: 489000);

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    byruhaaFakePaidThawaniSession($payment);
    Queue::fake();

    app(ConfirmThawaniPayment::class)->confirm($payment);

    $job = byruhaaQueuedWebhook('https://partner.test/webhooks/payment-paid');
    $payload = $job->payload;
    $payment->refresh();

    $delivery = byruhaaWebhookDelivery('payment.paid', $payment, 'https://partner.test/webhooks/payment-paid');

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Queued)
        ->and($delivery->queued_at)->not->toBeNull()
        ->and($job->meta)->toHaveKey('webhook_delivery_id', $delivery->id)
        ->and($payload['event'])->toBe('payment.paid')
        ->and($payload['customer_phone'])->toBe($payment->bookingInstallment->paymentSchedule->booking->customer->phone_number)
        ->and($payload['data']['payment']['id'])->toBe($payment->id)
        ->and($payload['data']['payment']['provider'])->toBe('thawani')
        ->and($payload['data']['payment']['status'])->toBe('paid')
        ->and($payload['data']['payment']['provider_status'])->toBe('paid')
        ->and($payload['data']['payment']['amount'])->toBe('489.000')
        ->and($payload['data']['payment']['provider_reference'])->toBe('payment_'.$payment->id)
        ->and($payload['data']['booking']['payment_status'])->toBe('paid')
        ->and($payload['data']['booking']['customer_panel_url'])->toBe(route('customer.bookings.show', $payment->bookingInstallment->paymentSchedule->booking))
        ->and($payload['data']['customer']['id'])->toBe($payment->bookingInstallment->paymentSchedule->booking->customer_id)
        ->and($payload['data']['customer']['phone'])->toBe($payment->bookingInstallment->paymentSchedule->booking->customer->phone_number)
        ->and($payload['data'])->not->toHaveKey('installments');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('partial payment webhook includes installment summary and items', function () {
    $payment = byruhaaWebhookPaymentFixture(amountBaisa: 200000, remainingAmountBaisa: 289000);

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    byruhaaFakePaidThawaniSession($payment);
    Queue::fake();

    app(ConfirmThawaniPayment::class)->confirm($payment);

    $payload = byruhaaQueuedWebhook('https://partner.test/webhooks/payment-paid')->payload;

    expect($payload['data']['booking']['payment_status'])->toBe('partially_paid')
        ->and($payload['data']['installments']['paid_installments_count'])->toBe(1)
        ->and($payload['data']['installments']['total_installments_count'])->toBe(2)
        ->and($payload['data']['installments']['paid_amount'])->toBe('200.000')
        ->and($payload['data']['installments']['remaining_amount'])->toBe('289.000')
        ->and($payload['data']['installments']['items'][0]['status'])->toBe('paid')
        ->and($payload['data']['installments']['items'][0]['amount'])->toBe('200.000')
        ->and($payload['data']['installments']['items'][1]['status'])->toBe('pending')
        ->and($payload['data']['installments']['items'][1]['amount'])->toBe('289.000');

    expect(json_encode($payload))->not->toContain('_baisa');
});

test('repeated paid thawani confirmation does not queue duplicate webhooks', function () {
    $payment = byruhaaWebhookPaymentFixture(amountBaisa: 489000);

    config([
        'byruhaa.webhooks.payment_paid_url' => 'https://partner.test/webhooks/payment-paid',
    ]);

    byruhaaFakePaidThawaniSession($payment);
    Queue::fake();

    $confirmedPayment = app(ConfirmThawaniPayment::class)->confirm($payment);
    app(ConfirmThawaniPayment::class)->confirm($confirmedPayment->refresh());

    Queue::assertPushed(CallWebhookJob::class, 1);
    expect(WebhookDelivery::query()->whereMorphedTo('webhookable', $payment)->where('event', 'payment.paid')->count())->toBe(1);
});

test('webhook delivery audit is marked delivered after successful call', function () {
    $delivery = WebhookDelivery::factory()->create([
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
        'status' => WebhookDeliveryStatus::Queued,
        'queued_at' => now(),
    ]);

    event(new WebhookCallSucceededEvent(
        'post',
        $delivery->webhook_url,
        $delivery->payload,
        [],
        ['webhook_delivery_id' => $delivery->id],
        [],
        2,
        new Response(200, [], 'ok'),
        null,
        null,
        $delivery->uuid,
        null,
    ));

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Delivered)
        ->and($delivery->attempts)->toBe(2)
        ->and($delivery->delivered_at)->not->toBeNull()
        ->and($delivery->response_status)->toBe(200)
        ->and($delivery->response_body)->toBe('ok');
});

test('webhook delivery audit records failed and final failed attempts', function () {
    $delivery = WebhookDelivery::factory()->create([
        'uuid' => '123e4567-e89b-12d3-a456-426614174001',
        'status' => WebhookDeliveryStatus::Queued,
        'queued_at' => now(),
    ]);

    event(new WebhookCallFailedEvent(
        'post',
        $delivery->webhook_url,
        $delivery->payload,
        [],
        ['webhook_delivery_id' => $delivery->id],
        [],
        1,
        new Response(500, [], 'server error'),
        'RuntimeException',
        'Webhook failed',
        $delivery->uuid,
        null,
    ));

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->failed_at)->not->toBeNull()
        ->and($delivery->response_status)->toBe(500)
        ->and($delivery->error_type)->toBe('RuntimeException')
        ->and($delivery->error_message)->toBe('Webhook failed');

    event(new FinalWebhookCallFailedEvent(
        'post',
        $delivery->webhook_url,
        $delivery->payload,
        [],
        ['webhook_delivery_id' => $delivery->id],
        [],
        3,
        new Response(500, [], 'final error'),
        'RuntimeException',
        'Webhook finally failed',
        $delivery->uuid,
        null,
    ));

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::FinalFailed)
        ->and($delivery->attempts)->toBe(3)
        ->and($delivery->final_failed_at)->not->toBeNull()
        ->and($delivery->response_body)->toBe('final error')
        ->and($delivery->error_message)->toBe('Webhook finally failed');
});

/**
 * @return array{0: Booking, 1: User}
 */
function byruhaaWebhookBookingFixture(int $familyMemberCount = 1): array
{
    $staff = User::factory()->create();
    $customer = Customer::factory()->create([
        'name' => 'Mona Webhook',
        'email' => 'mona.webhook@example.com',
        'phone_number' => '+96891234567',
    ]);
    $event = Event::factory()->create([
        'name' => 'Webhook Camp',
        'seat_capacity' => 10,
        'price_baisa' => 489000,
    ]);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'reference' => 'BRH-WEBHOOK',
        'unit_price_baisa' => 489000,
        'family_member_count' => $familyMemberCount,
        'subtotal_baisa' => 489000 * $familyMemberCount,
        'discount_amount_baisa' => 0,
        'total_baisa' => 489000 * $familyMemberCount,
        'currency' => 'OMR',
    ]);

    FamilyMember::factory()
        ->count($familyMemberCount)
        ->sequence(fn (Sequence $sequence): array => [
            'name' => $sequence->index === 0 ? 'Maha Webhook' : 'Maha Webhook '.$sequence->index,
        ])
        ->for($customer)
        ->create()
        ->each(fn (FamilyMember $familyMember): BookingFamilyMember => BookingFamilyMember::factory()->for($booking)->for($familyMember)->create());

    return [$booking, $staff];
}

/**
 * @return array{0: Customer, 1: Event, 2: Collection<int, FamilyMember>}
 */
function byruhaaBookingCreationFixture(): array
{
    $customer = Customer::factory()->create([
        'name' => 'Created Booking Customer',
        'email' => 'created.booking@example.com',
        'phone_number' => '+96892345678',
    ]);
    $event = Event::factory()->create([
        'name' => 'Created Booking Camp',
        'seat_capacity' => 10,
        'price_baisa' => 12000,
        'minimum_age' => 9,
        'maximum_age' => 16,
    ]);
    $familyMembers = FamilyMember::factory()
        ->count(2)
        ->sequence(
            ['name' => 'First Created Member', 'birth_date' => now()->subYears(12)->toDateString()],
            ['name' => 'Second Created Member', 'birth_date' => now()->subYears(13)->toDateString()],
        )
        ->for($customer)
        ->create();

    return [$customer, $event, $familyMembers];
}

function byruhaaWebhookPaymentFixture(int $amountBaisa, ?int $remainingAmountBaisa = null): Payment
{
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'Payment Webhook Camp']);
    $totalBaisa = $amountBaisa + ($remainingAmountBaisa ?? 0);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'reference' => 'BRH-PAY-WEBHOOK-'.$amountBaisa,
        'state' => Approved::$name,
        'currency' => 'OMR',
        'subtotal_baisa' => $totalBaisa,
        'total_baisa' => $totalBaisa,
    ]);
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'event_payment_plan_id' => null,
        'plan_name' => 'Webhook payment plan',
        'currency' => 'OMR',
        'subtotal_baisa' => $totalBaisa,
        'total_baisa' => $totalBaisa,
    ]);
    $firstInstallment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'First',
        'sequence' => 1,
        'percentage' => $remainingAmountBaisa === null ? 100 : 50,
        'amount_baisa' => $amountBaisa,
        'currency' => 'OMR',
        'state' => BookingInstallmentState::Pending,
    ]);

    if ($remainingAmountBaisa !== null) {
        BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
            'name' => 'Final',
            'sequence' => 2,
            'percentage' => 50,
            'amount_baisa' => $remainingAmountBaisa,
            'currency' => 'OMR',
            'state' => BookingInstallmentState::Pending,
        ]);
    }

    return Payment::factory()->for($firstInstallment, 'bookingInstallment')->create([
        'amount_baisa' => $amountBaisa,
        'currency' => 'OMR',
        'state' => PaymentState::Pending,
        'provider_session_id' => 'checkout_session_'.$amountBaisa,
    ]);
}

function byruhaaFakePaidThawaniSession(Payment $payment): void
{
    Http::preventStrayRequests();
    Http::fake([
        "https://uatcheckout.thawani.om/api/v1/checkout/session/{$payment->provider_session_id}" => Http::response([
            'success' => true,
            'data' => [
                'session_id' => $payment->provider_session_id,
                'payment_status' => 'paid',
                'payment_id' => 'payment_'.$payment->id,
                'invoice' => 'invoice_'.$payment->id,
                'total_amount' => $payment->amount_baisa,
            ],
        ]),
    ]);
}

function byruhaaQueuedWebhook(string $url): CallWebhookJob
{
    $queuedJob = null;

    Queue::assertPushed(CallWebhookJob::class, function (CallWebhookJob $job) use (&$queuedJob, $url): bool {
        if ($job->webhookUrl !== $url) {
            return false;
        }

        $queuedJob = $job;

        return true;
    });

    expect($queuedJob)->toBeInstanceOf(CallWebhookJob::class);

    return $queuedJob;
}

function byruhaaSignatureDataUrl(): string
{
    return 'data:image/png;base64,'.base64_encode('fake-png');
}

function byruhaaWebhookDelivery(string $event, Booking|Customer|EventCancellation|EventInterest|Payment|PaymentRefund $webhookable, string $url): WebhookDelivery
{
    $delivery = WebhookDelivery::query()
        ->where('event', $event)
        ->where('webhook_url_hash', hash('sha256', $url))
        ->whereMorphedTo('webhookable', $webhookable)
        ->sole();

    expect($delivery)->toBeInstanceOf(WebhookDelivery::class);

    return $delivery;
}
