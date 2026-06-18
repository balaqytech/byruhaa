<?php

use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\Customer;
use App\Models\Event;
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

    app(BookingApprovalService::class)->approve($booking, $staff);

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

    app(BookingApprovalService::class)->approve($booking, $staff);

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

    app(BookingApprovalService::class)->approve($booking, $staff);

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
