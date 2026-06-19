<?php

use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPaymentPlan;
use App\Models\EventPaymentPlanInstallment;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\BookingApprovalService;
use App\Services\ContractRenderer;
use App\States\Contract\Signed;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('customer can sign an approved contract with a drawn png signature', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);

    $contract = $bookingFamilyMember->contract()->firstOrFail();
    $signature = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

    $contract->sign($signature, $customer->name, '127.0.0.1');

    $contract->refresh();

    expect($contract->state)->toBeInstanceOf(Signed::class)
        ->and($contract->signed_name)->toBe($customer->name)
        ->and($contract->signature_path)->not->toBeNull();

    Storage::disk('local')->assertExists($contract->signature_path);
});

test('customer can sign a contract from the booking details page action', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create(['name' => 'Salim Al Balushi']);
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);

    $contract = $bookingFamilyMember->contract()->firstOrFail();
    $signature = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::bookings.show', ['booking' => $booking])
        ->set('signedName', $customer->name)
        ->call('signContract', $contract->id, $signature)
        ->assertHasNoErrors();

    expect($contract->refresh())
        ->state->toBeInstanceOf(Signed::class)
        ->signed_name->toBe($customer->name);

    Storage::disk('local')->assertExists($contract->signature_path);
});

test('event contract snapshot and pdf are rendered in arabic', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create(['name' => 'سالم البلوشي']);
    $event = Event::factory()->create([
        'name' => 'مخيم الربيع',
        'location' => 'مسقط',
        'contract_terms_html' => '<p>يلتزم ولي الأمر بتعليمات السلامة والحضور في الوقت المحدد.</p>',
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create(['name' => 'مها البلوشية']);
    $booking = Booking::factory()->for($customer)->for($event)->create(['reference' => 'BRH-ARABIC']);
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);

    $contract = $bookingFamilyMember->contract()->firstOrFail();

    expect($contract->contract_html)
        ->toContain('الشروط والأحكام الخاصة بفعالية')
        ->toContain('مخيم الربيع')
        ->toContain('يلتزم ولي الأمر بتعليمات السلامة');

    $signature = 'data:image/png;base64,'.'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    $contract->sign($signature, $customer->name, '127.0.0.1');

    $pdf = app(ContractRenderer::class)->pdf($contract->refresh());

    expect($pdf)
        ->toStartWith('%PDF')
        ->toContain('/Type /Page');
});

test('customer cannot select a payment plan before all contracts are signed', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'subtotal_baisa' => 10000,
        'total_baisa' => 10000,
    ]);

    BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);

    $paymentPlan = EventPaymentPlan::factory()->for($event)->create(['name' => 'Two payments']);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'sequence' => 1,
        'percentage' => 100,
        'due_date' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::bookings.show', ['booking' => $booking])
        ->set('paymentPlanId', $paymentPlan->id)
        ->call('selectPaymentPlan')
        ->assertHasErrors('paymentPlanId');

    expect(BookingInstallment::query()->count())->toBe(0);
});

test('customer can select a payment plan after all contracts are signed', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'currency' => 'OMR',
        'subtotal_baisa' => 10001,
        'discount_amount_baisa' => 1000,
        'total_baisa' => 9001,
    ]);
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);

    $contract = $bookingFamilyMember->contract()->firstOrFail();
    $contract->sign('data:image/png;base64,'.base64_encode('fake-png-bytes'), $customer->name, '127.0.0.1');

    $paymentPlan = EventPaymentPlan::factory()->for($event)->create(['name' => 'Three payments']);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'First',
        'sequence' => 1,
        'percentage' => 33,
        'due_date' => '2026-07-01',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Second',
        'sequence' => 2,
        'percentage' => 33,
        'due_date' => '2026-08-01',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Final',
        'sequence' => 3,
        'percentage' => 34,
        'due_date' => '2026-09-01',
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::bookings.show', ['booking' => $booking])
        ->assertSee('Three payments')
        ->set('paymentPlanId', $paymentPlan->id)
        ->call('selectPaymentPlan')
        ->assertHasNoErrors()
        ->assertSee('First')
        ->assertSee('Final')
        ->assertSee('data-omr-symbol', false)
        ->assertSee('3.061');

    $installments = $booking->refresh()->paymentSchedule()->firstOrFail()->installments()->get();

    expect($installments)->toHaveCount(3)
        ->and($installments->pluck('gross_amount_baisa')->all())->toBe([3300, 3300, 3401])
        ->and($installments->pluck('discount_amount_baisa')->all())->toBe([330, 330, 340])
        ->and($installments->pluck('amount_baisa')->all())->toBe([2970, 2970, 3061])
        ->and($installments->sum('amount_baisa'))->toBe(9001);
});

test('payment plan selection rejects invalid percentage totals', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'subtotal_baisa' => 10000,
        'total_baisa' => 10000,
    ]);
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);

    $contract = $bookingFamilyMember->contract()->firstOrFail();
    $contract->sign('data:image/png;base64,'.base64_encode('fake-png-bytes'), $customer->name, '127.0.0.1');

    $paymentPlan = EventPaymentPlan::factory()->for($event)->create();
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'sequence' => 1,
        'percentage' => 50,
        'due_date' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::bookings.show', ['booking' => $booking])
        ->set('paymentPlanId', $paymentPlan->id)
        ->call('selectPaymentPlan')
        ->assertHasErrors('paymentPlanId');

    expect($booking->refresh()->paymentSchedule)->toBeNull();
});
