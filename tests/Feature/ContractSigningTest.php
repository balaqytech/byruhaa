<?php

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventContract;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Models\EventPaymentPlanInstallment;
use App\Modules\Events\Services\BookingApprovalService;
use App\Modules\Events\Services\ContractRenderer;
use App\Modules\Events\States\Booking\Approved;
use App\Modules\Events\States\Contract\Signed;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\User;
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

test('customer can sign a contract from the dedicated contract page action', function () {
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

    Livewire::test('pages::customer.bookings.contract', ['booking' => $booking, 'contract' => $contract])
        ->set('signedName', $customer->name)
        ->call('signContract', $signature)
        ->assertHasNoErrors();

    expect($contract->refresh())
        ->state->toBeInstanceOf(Signed::class)
        ->signed_name->toBe($customer->name);

    Storage::disk('local')->assertExists($contract->signature_path);
});

test('customer with incomplete profile cannot sign a contract', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->incompleteProfile()->create(['name' => 'Salim Al Balushi']);
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);

    $contract = $bookingFamilyMember->contract()->firstOrFail();
    $signature = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.contract', ['booking' => $booking, 'contract' => $contract])
        ->set('signedName', $customer->name)
        ->call('signContract', $signature)
        ->assertHasErrors(['profile']);

    expect($contract->refresh())
        ->state->not->toBeInstanceOf(Signed::class)
        ->signed_name->toBeNull()
        ->signature_path->toBeNull();
});

test('booking show page lists participant contract cards without rendering contract bodies inline', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Explorer Camp',
        'contract_terms_html' => '<p>Inline body should only appear on the contract page.</p>',
    ]);
    $firstFamilyMember = FamilyMember::factory()->for($customer)->create(['name' => 'First Participant']);
    $secondFamilyMember = FamilyMember::factory()->for($customer)->create(['name' => 'Second Participant']);
    $booking = Booking::factory()->for($customer)->for($event)->create(['reference' => 'BRH-CARDS']);
    $firstBookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($firstFamilyMember)->create();
    $secondBookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($secondFamilyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);
    $firstContract = $firstBookingFamilyMember->contract()->firstOrFail();
    $secondContract = $secondBookingFamilyMember->contract()->firstOrFail();

    $firstContract->sign('data:image/png;base64,'.base64_encode('fake-png-bytes'), $customer->name, '127.0.0.1');

    $this->actingAs($customer, 'customer')
        ->get(route('customer.bookings.show', $booking))
        ->assertOk()
        ->assertSee('First Participant')
        ->assertSee('Second Participant')
        ->assertSee(__('ui.actions.view_contract'))
        ->assertSee(__('ui.actions.view_and_sign_contract'))
        ->assertSee(__('ui.actions.download_pdf'))
        ->assertSee($firstContract->refresh()->signed_at->format('Y-m-d H:i'))
        ->assertSee(route('customer.bookings.contracts.show', [$booking, $firstContract]), false)
        ->assertSee(route('customer.bookings.contracts.show', [$booking, $secondContract]), false)
        ->assertDontSee('Inline body should only appear on the contract page.')
        ->assertDontSee('<canvas', false);
});

test('customer can open their own participant contract page', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Own Contract Event',
        'contract_terms_html' => '<p>Only this contract page renders these terms.</p>',
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create(['name' => 'Own Participant']);
    $booking = Booking::factory()->for($customer)->for($event)->create(['reference' => 'BRH-OWN']);
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);
    $contract = $bookingFamilyMember->contract()->firstOrFail();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.bookings.contracts.show', [$booking, $contract]))
        ->assertOk()
        ->assertSee('Own Participant')
        ->assertSee('Only this contract page renders these terms.')
        ->assertSee(__('ui.actions.sign_contract'));
});

test('customer cannot open a contract that does not belong to their booking', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $event = Event::factory()->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $otherBooking = Booking::factory()->for($otherCustomer)->for($event)->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $otherFamilyMember = FamilyMember::factory()->for($otherCustomer)->create();

    BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();
    $otherBookingFamilyMember = BookingFamilyMember::factory()->for($otherBooking)->for($otherFamilyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);
    app(BookingApprovalService::class)->approve($otherBooking, $staff);
    $otherContract = $otherBookingFamilyMember->contract()->firstOrFail();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.bookings.contracts.show', [$booking, $otherContract]))
        ->assertNotFound();
});

test('signed contract can be downloaded from the dedicated contract page', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'contract_terms_html' => '<p>Downloadable contract body.</p>',
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create(['reference' => 'BRH-PDF']);
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);
    $contract = $bookingFamilyMember->contract()->firstOrFail();
    $contract->sign('data:image/png;base64,'.base64_encode('fake-png-bytes'), $customer->name, '127.0.0.1');

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.contract', ['booking' => $booking, 'contract' => $contract])
        ->assertSee(__('ui.actions.download_pdf'))
        ->call('downloadContract')
        ->assertFileDownloaded("byruhaa-contract-{$booking->reference}-{$contract->id}.pdf");
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

test('contract variables are rendered and escaped when booking is approved', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create([
        'name' => 'Mona <Guardian>',
        'civil_id' => 'OM123456',
        'wilaya' => 'Muscat',
        'area' => 'Qurum',
        'address' => 'House 12',
    ]);
    $event = Event::factory()->create([
        'name' => 'Spring Camp',
        'location' => 'Muscat',
        'starts_at' => '2026-07-01 09:00:00',
        'ends_at' => '2026-07-03 12:30:00',
        'price_baisa' => 12000,
        'contract_terms_html' => implode('', [
            '<p>Guardian: {{ guardian_name }}</p>',
            '<p>Student: {{ student_name }}</p>',
            '<p>Event: {{ event_name }}</p>',
            '<p>Start: {{ event_start_date }}</p>',
            '<p>End: {{ event_end_date }}</p>',
            '<p>Fee: {{ agreed_fee }}</p>',
            '<p>Area: {{ guardian_area }}</p>',
            '<p>Address: {{ guardian_address }}</p>',
            '<p>Relationship: {{ guardian_relationship }}</p>',
        ]),
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create([
        'name' => 'Maha & Salim',
        'birth_date' => '2014-05-10',
        'relationship_to_customer' => 'Daughter',
    ]);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'reference' => 'BRH-VARS',
        'currency' => 'OMR',
        'subtotal_baisa' => 12000,
        'discount_amount_baisa' => 3000,
        'total_baisa' => 9000,
    ]);

    BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    app(BookingApprovalService::class)->approve($booking, $staff);

    $html = EventContract::query()->firstOrFail()->contract_html;

    expect($html)
        ->toContain('Mona &lt;Guardian&gt;')
        ->toContain('Maha &amp; Salim')
        ->toContain('Spring Camp')
        ->toContain('2026-07-01 09:00')
        ->toContain('2026-07-03 12:30')
        ->toContain('OMR 9.000')
        ->toContain('Qurum')
        ->toContain('House 12')
        ->toContain('Daughter')
        ->not->toContain('{{')
        ->not->toContain('<Guardian>')
        ->not->toContain('Maha & Salim');
});

test('contract amount variables are rendered per family member', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Per Member Camp',
        'price_baisa' => 12000,
        'contract_terms_html' => implode('', [
            '<p>Subtotal: {{ subtotal }}</p>',
            '<p>Discount: {{ discount_amount }}</p>',
            '<p>Total: {{ total_amount }}</p>',
            '<p>Agreed: {{ agreed_fee }}</p>',
        ]),
    ]);
    $firstFamilyMember = FamilyMember::factory()->for($customer)->create(['name' => 'First Participant']);
    $secondFamilyMember = FamilyMember::factory()->for($customer)->create(['name' => 'Second Participant']);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'reference' => 'BRH-PER-MEMBER',
        'unit_price_baisa' => 12000,
        'currency' => 'OMR',
        'family_member_count' => 2,
        'subtotal_baisa' => 24000,
        'discount_amount_baisa' => 4000,
        'total_baisa' => 20000,
    ]);
    $firstBookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($firstFamilyMember)->create();
    $secondBookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($secondFamilyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);
    $firstContract = $firstBookingFamilyMember->contract()->firstOrFail();
    $secondContract = $secondBookingFamilyMember->contract()->firstOrFail();

    foreach ([$firstContract, $secondContract] as $contract) {
        expect($contract->contract_html)
            ->toContain('Subtotal: OMR 12.000')
            ->toContain('Discount: OMR 2.000')
            ->toContain('Total: OMR 10.000')
            ->toContain('Agreed: OMR 10.000')
            ->not->toContain('OMR 24.000')
            ->not->toContain('OMR 20.000');
    }

    $this->actingAs($customer, 'customer')
        ->get(route('customer.bookings.contracts.show', [$booking, $firstContract]))
        ->assertOk()
        ->assertSee('Subtotal: OMR 12.000')
        ->assertSee('Discount: OMR 2.000')
        ->assertSee('Total: OMR 10.000')
        ->assertSee('Agreed: OMR 10.000')
        ->assertDontSee('OMR 24.000')
        ->assertDontSee('OMR 20.000');

    $firstContract->loadMissing('bookingFamilyMember.booking.customer', 'bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember');

    $pdfHtml = view('contracts.event-pdf', [
        'contract' => $firstContract,
        'bookingFamilyMember' => $firstContract->bookingFamilyMember,
        'booking' => $firstContract->bookingFamilyMember->booking,
        'customer' => $firstContract->bookingFamilyMember->booking->customer,
        'event' => $firstContract->bookingFamilyMember->booking->event,
        'familyMember' => $firstContract->bookingFamilyMember->familyMember,
    ])->render();

    expect($pdfHtml)
        ->toContain('Subtotal: OMR 12.000')
        ->toContain('Discount: OMR 2.000')
        ->toContain('Total: OMR 10.000')
        ->toContain('Agreed: OMR 10.000')
        ->not->toContain('OMR 24.000')
        ->not->toContain('OMR 20.000');
});

test('filament rich editor merge tags are rendered when booking is approved', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create([
        'name' => 'Mona <Guardian>',
    ]);
    $event = Event::factory()->create([
        'name' => 'Merge Tag Camp',
        'contract_terms_html' => implode('', [
            '<p>Guardian: <span data-type="mergeTag" data-id="guardian_name"></span></p>',
            '<p>Student: <span data-type="mergeTag" data-id="student_name"></span></p>',
            '<p>Fee: <span data-type="mergeTag" data-id="agreed_fee"></span></p>',
        ]),
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create([
        'name' => 'Maha & Salim',
    ]);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'currency' => 'OMR',
        'total_baisa' => 9000,
    ]);

    BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    app(BookingApprovalService::class)->approve($booking, $staff);

    $html = EventContract::query()->firstOrFail()->contract_html;

    expect($html)
        ->toContain('Mona &lt;Guardian&gt;')
        ->toContain('Maha &amp; Salim')
        ->toContain('OMR 9.000')
        ->not->toContain('data-type="mergeTag"')
        ->not->toContain('<Guardian>')
        ->not->toContain('Maha & Salim');
});

test('required participant extra fields block contract signing', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'participant_extra_fields' => [
            [
                'key' => 'medical_clearance',
                'label' => 'Medical clearance',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);
    $contract = $bookingFamilyMember->contract()->firstOrFail();
    $signature = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.contract', ['booking' => $booking, 'contract' => $contract])
        ->set('signedName', $customer->name)
        ->call('signContract', $signature)
        ->assertHasErrors(['participantExtraAnswers.medical_clearance']);

    expect($contract->refresh())
        ->state->not->toBeInstanceOf(Signed::class)
        ->participant_extra_answers->toBeNull()
        ->participant_extra_completed_at->toBeNull();
});

test('participant extra answers are saved on the correct contract and rendered for view and pdf', function () {
    Storage::fake('local');

    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'participant_extra_fields' => [
            [
                'key' => 'swimming_level',
                'label' => 'Swimming level',
                'type' => 'select',
                'required' => true,
                'options' => "Beginner\nAdvanced",
            ],
            [
                'key' => 'special_notes',
                'label' => 'Special notes',
                'type' => 'textarea',
                'required' => false,
            ],
        ],
    ]);
    $firstFamilyMember = FamilyMember::factory()->for($customer)->create(['name' => 'First Student']);
    $secondFamilyMember = FamilyMember::factory()->for($customer)->create(['name' => 'Second Student']);
    $booking = Booking::factory()->for($customer)->for($event)->create(['reference' => 'BRH-EXTRA']);
    $firstBookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($firstFamilyMember)->create();
    $secondBookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($secondFamilyMember)->create();

    $booking = app(BookingApprovalService::class)->approve($booking, $staff);
    $firstContract = $firstBookingFamilyMember->contract()->firstOrFail();
    $secondContract = $secondBookingFamilyMember->contract()->firstOrFail();
    $signature = 'data:image/png;base64,'.'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.contract', ['booking' => $booking, 'contract' => $firstContract])
        ->set('participantExtraAnswers.swimming_level', 'Advanced')
        ->set('participantExtraAnswers.special_notes', 'Needs shade <script>')
        ->set('signedName', $customer->name)
        ->call('signContract', $signature)
        ->assertHasNoErrors();

    expect($firstContract->refresh())
        ->state->toBeInstanceOf(Signed::class)
        ->participant_extra_answers->toBe([
            'swimming_level' => 'Advanced',
            'special_notes' => 'Needs shade <script>',
        ])
        ->participant_extra_completed_at->not->toBeNull()
        ->and($secondContract->refresh()->participant_extra_answers)->toBeNull();

    $this->get(route('customer.bookings.contracts.show', [$booking, $firstContract]))
        ->assertOk()
        ->assertSee('Swimming level')
        ->assertSee('Advanced')
        ->assertSee('Needs shade &lt;script&gt;', false)
        ->assertDontSee('Needs shade <script>', false);

    $firstContract->loadMissing('bookingFamilyMember.booking.customer', 'bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember');

    $pdfHtml = view('contracts.event-pdf', [
        'contract' => $firstContract,
        'bookingFamilyMember' => $firstContract->bookingFamilyMember,
        'booking' => $firstContract->bookingFamilyMember->booking,
        'customer' => $firstContract->bookingFamilyMember->booking->customer,
        'event' => $firstContract->bookingFamilyMember->booking->event,
        'familyMember' => $firstContract->bookingFamilyMember->familyMember,
    ])->render();

    expect($pdfHtml)
        ->toContain('Swimming level')
        ->toContain('Advanced')
        ->toContain('Needs shade &lt;script&gt;')
        ->not->toContain('Needs shade <script>')
        ->and(app(ContractRenderer::class)->pdf($firstContract))->toStartWith('%PDF');
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

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->set('paymentPlanId', $paymentPlan->id)
        ->call('selectPaymentPlan')
        ->assertHasErrors('paymentPlanId');

    expect(BookingInstallment::query()->count())->toBe(0);
});

test('customer with incomplete profile cannot select a payment plan', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'state' => Approved::$name,
        'subtotal_baisa' => 10000,
        'total_baisa' => 10000,
    ]);
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    EventContract::factory()->for($bookingFamilyMember)->create([
        'state' => Signed::$name,
        'signed_at' => now(),
    ]);

    $paymentPlan = EventPaymentPlan::factory()->for($event)->create(['name' => 'Two payments']);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'sequence' => 1,
        'percentage' => 100,
        'due_date' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->set('paymentPlanId', $paymentPlan->id)
        ->call('selectPaymentPlan')
        ->assertHasErrors(['paymentPlanId']);

    expect($booking->refresh()->paymentSchedule)->toBeNull();
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

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
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

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->set('paymentPlanId', $paymentPlan->id)
        ->call('selectPaymentPlan')
        ->assertHasErrors('paymentPlanId');

    expect($booking->refresh()->paymentSchedule)->toBeNull();
});

test('customer with incomplete profile cannot start full payment', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $event = Event::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'state' => Approved::$name,
        'currency' => 'OMR',
        'subtotal_baisa' => 12000,
        'total_baisa' => 12000,
    ]);
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    EventContract::factory()->for($bookingFamilyMember)->create([
        'state' => Signed::$name,
        'signed_at' => now(),
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->call('payInFull')
        ->assertHasErrors(['payment']);

    expect($booking->refresh()->paymentSchedule)->toBeNull()
        ->and(Payment::query()->count())->toBe(0);
});

test('customer with incomplete profile cannot start installment payment', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $event = Event::factory()->create();
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'state' => Approved::$name,
        'currency' => 'OMR',
        'subtotal_baisa' => 12000,
        'total_baisa' => 12000,
    ]);
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'currency' => 'OMR',
        'subtotal_baisa' => 12000,
        'total_baisa' => 12000,
    ]);
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'First',
        'sequence' => 1,
        'percentage' => 100,
        'amount_baisa' => 12000,
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.bookings.show', ['booking' => $booking])
        ->call('payInstallment', $installment->id)
        ->assertHasErrors(['payment']);

    expect(Payment::query()->count())->toBe(0);
});
