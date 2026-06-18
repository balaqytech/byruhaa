<?php

use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\BookingApprovalService;
use App\States\Contract\Signed;
use Illuminate\Support\Facades\Storage;

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
