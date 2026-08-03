<?php

namespace App\Modules\Events\Actions;

use App\Enums\BookingInstallmentState;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentState;
use App\Models\WebhookDelivery;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Services\ContractRenderer;
use App\Modules\Events\States\Booking\Approved;
use App\Modules\Finance\Actions\PostPaymentLedgerTransaction;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use RuntimeException;

class ImportMigrantsEventBookings
{
    private const EventName = 'فعالية مهاجر إلى ربي - صيف 2026';

    private const EventSlug = 'muhajir-ila-rabbi-summer-2026';

    private const SourceName = 'migrants_2026_workbook';

    private const FullPayment = 'دفعة كاملة';

    private const TwoInstallments = 'قسطين';

    private const FullyPaid = 'تم دفع كامل المبلغ';

    private const FirstInstallmentPaid = 'تم دفع القسط الأول';

    private const PaymentPledge = 'تعهد بالدفع';

    private const SendWebhookHeader = 'send webhook';

    public function __construct(
        private PhoneNumberNormalizer $phoneNumberNormalizer,
        private ContractRenderer $contractRenderer,
        private PostPaymentLedgerTransaction $postPaymentLedgerTransaction,
        private ByruhaaWebhookSender $webhookSender,
        private ReserveBookingSeats $reserveBookingSeats,
    ) {}

    /**
     * @return array{events: int, bookings: int, participants: int, payments: int, approval_webhooks: int}
     */
    public function execute(string $path, string $temporaryPassword): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("The workbook [{$path}] does not exist.");
        }

        if (blank($temporaryPassword)) {
            throw new InvalidArgumentException('The temporary password option is required.');
        }

        $rows = $this->rows($path);

        if ($rows->isEmpty()) {
            throw new InvalidArgumentException('The workbook does not contain any importable rows.');
        }

        $bookings = DB::transaction(function () use ($rows, $temporaryPassword): Collection {
            $event = $this->upsertEvent($rows->count());
            $discounts = $this->upsertDiscounts($event);
            $paymentPlan = $this->upsertPaymentPlan($event);

            return $this->importBookings($rows, $temporaryPassword, $event, $discounts, $paymentPlan);
        });

        $approvalWebhookCount = $this->sendApprovalWebhooks($bookings);

        return [
            'events' => 1,
            'bookings' => $bookings->count(),
            'participants' => $rows->count(),
            'payments' => Payment::query()
                ->whereHas('bookingInstallment.paymentSchedule.booking', fn ($query) => $query
                    ->whereIn('id', $bookings->pluck('id')))
                ->count(),
            'approval_webhooks' => $approvalWebhookCount,
        ];
    }

    /**
     * @return Collection<int, array{
     *     row_number: int,
     *     registered_at: CarbonImmutable,
     *     student_name: string,
     *     grade: string|null,
     *     birth_date: CarbonImmutable,
     *     relationship: string|null,
     *     guardian_name: string,
     *     civil_id: string,
     *     phone_number: string,
     *     email: string|null,
     *     address: string,
     *     amount_baisa: int,
     *     payment_method: string|null,
     *     status: string
     * }>
     */
    private function rows(string $path): Collection
    {
        $reader = ReaderFactory::createFromFile($path);
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $headers = null;
                $rows = collect();

                foreach ($sheet->getRowIterator() as $index => $row) {
                    $values = $row->toArray();

                    if ($index === 1) {
                        $headers = $this->headers($values);

                        continue;
                    }

                    if ($this->rowIsBlank($values)) {
                        continue;
                    }

                    if ($headers === null) {
                        throw new InvalidArgumentException('The workbook is missing a header row.');
                    }

                    $rows->push($this->row($headers, $values, $index));
                }

                return $rows;
            }
        } finally {
            $reader->close();
        }

        return collect();
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string, int>
     */
    private function headers(array $values): array
    {
        $headers = [];

        foreach ($values as $index => $value) {
            $headers[$this->string($value)] = $index;
        }

        foreach ($this->requiredHeaders() as $header) {
            if (! array_key_exists($header, $headers)) {
                throw new InvalidArgumentException("The workbook is missing the [{$header}] column.");
            }
        }

        return $headers;
    }

    /**
     * @return array<int, string>
     */
    private function requiredHeaders(): array
    {
        return [
            'تاريخ التسجيل',
            'اسم الطالب',
            'الصف الدراسي',
            'تاريخ الميلاد',
            'صلة القرابة',
            'اسم ولي الأمر',
            'الرقم المدني',
            'هاتف ولي الأمر',
            'البريد الإلكتروني',
            'العنوان التفصيلي',
            'المبلغ المطلوب دفعه',
            'طريقة الدفع',
            'حالة الطلب',
        ];
    }

    /**
     * @param  array<string, int>  $headers
     * @param  array<int, mixed>  $values
     * @return array{
     *     row_number: int,
     *     registered_at: CarbonImmutable,
     *     student_name: string,
     *     grade: string|null,
     *     birth_date: CarbonImmutable,
     *     relationship: string|null,
     *     guardian_name: string,
     *     civil_id: string,
     *     phone_number: string,
     *     email: string|null,
     *     address: string,
     *     amount_baisa: int,
     *     payment_method: string|null,
     *     status: string,
     *     send_webhook: bool
     * }
     */
    private function row(array $headers, array $values, int $rowNumber): array
    {
        $phoneNumber = $this->phoneNumberNormalizer->normalize($this->cell($values, $headers, 'هاتف ولي الأمر'));

        if ($phoneNumber === null) {
            throw new InvalidArgumentException("Row {$rowNumber} is missing a valid phone number.");
        }

        $paymentMethod = $this->nullableString($this->cell($values, $headers, 'طريقة الدفع'));
        $status = $this->string($this->cell($values, $headers, 'حالة الطلب'));

        $this->validatePaymentState($paymentMethod, $status, $rowNumber);

        return [
            'row_number' => $rowNumber,
            'registered_at' => $this->date($this->cell($values, $headers, 'تاريخ التسجيل'), $rowNumber, 'تاريخ التسجيل'),
            'student_name' => $this->requiredString($this->cell($values, $headers, 'اسم الطالب'), $rowNumber, 'اسم الطالب'),
            'grade' => $this->nullableString($this->cell($values, $headers, 'الصف الدراسي')),
            'birth_date' => $this->date($this->cell($values, $headers, 'تاريخ الميلاد'), $rowNumber, 'تاريخ الميلاد'),
            'relationship' => $this->nullableString($this->cell($values, $headers, 'صلة القرابة')),
            'guardian_name' => $this->requiredString($this->cell($values, $headers, 'اسم ولي الأمر'), $rowNumber, 'اسم ولي الأمر'),
            'civil_id' => $this->requiredString($this->cell($values, $headers, 'الرقم المدني'), $rowNumber, 'الرقم المدني'),
            'phone_number' => $phoneNumber,
            'email' => $this->email($this->cell($values, $headers, 'البريد الإلكتروني')),
            'address' => $this->requiredString($this->cell($values, $headers, 'العنوان التفصيلي'), $rowNumber, 'العنوان التفصيلي'),
            'amount_baisa' => $this->money($this->cell($values, $headers, 'المبلغ المطلوب دفعه'), $rowNumber),
            'payment_method' => $paymentMethod,
            'status' => $status,
            'send_webhook' => $this->parseSendWebhookFlag($this->cell($values, $headers, self::SendWebhookHeader)),
        ];
    }

    private function validatePaymentState(?string $paymentMethod, string $status, int $rowNumber): void
    {
        $isValid = match ($status) {
            self::FullyPaid => $paymentMethod === self::FullPayment,
            self::FirstInstallmentPaid => $paymentMethod === self::TwoInstallments,
            self::PaymentPledge => $paymentMethod === null,
            default => false,
        };

        if (! $isValid) {
            throw new InvalidArgumentException("Row {$rowNumber} has an unsupported payment method/status combination.");
        }
    }

    private function upsertEvent(int $participantCount): Event
    {
        $event = Event::query()->firstOrNew(['slug' => self::EventSlug]);

        $event->forceFill([
            'name' => self::EventName,
            'type' => EventType::Camp,
            'status' => EventStatus::Published,
            'price_baisa' => 700000,
            'currency' => 'OMR',
            'minimum_age' => 9,
            'maximum_age' => 16,
            'seat_capacity' => max((int) ($event->seat_capacity ?: 0), 50, $participantCount),
        ])->save();

        return $event->refresh();
    }

    /**
     * @return array{early: Discount, siblings: Discount}
     */
    private function upsertDiscounts(Event $event): array
    {
        return [
            'early' => $this->upsertDiscount($event, 'خصم التسجيل المبكر', 211000, 1, 1),
            'siblings' => $this->upsertDiscount($event, 'خصم الأخوة', 231000, 2, 2),
        ];
    }

    private function upsertDiscount(Event $event, string $name, int $amountBaisa, int $minimumMembers, int $maximumMembers): Discount
    {
        return Discount::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => $name,
            ],
            [
                'amount_baisa' => $amountBaisa,
                'currency' => $event->currency,
                'starts_at' => null,
                'ends_at' => null,
                'minimum_family_members' => $minimumMembers,
                'maximum_family_members' => $maximumMembers,
                'is_active' => true,
            ],
        );
    }

    private function upsertPaymentPlan(Event $event): EventPaymentPlan
    {
        $paymentPlan = EventPaymentPlan::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => 'الدفع على قسطين',
            ],
            ['is_active' => true],
        );

        $paymentPlan->installments()->updateOrCreate(
            ['sequence' => 1],
            [
                'name' => 'القسط الأول',
                'percentage' => 50,
                'due_date' => Date::today()->toDateString(),
            ],
        );

        $paymentPlan->installments()->updateOrCreate(
            ['sequence' => 2],
            [
                'name' => 'القسط الثاني',
                'percentage' => 50,
                'due_date' => '2026-07-27',
            ],
        );

        return $paymentPlan->load('installments');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array{early: Discount, siblings: Discount}  $discounts
     * @return Collection<int, Booking>
     */
    private function importBookings(Collection $rows, string $temporaryPassword, Event $event, array $discounts, EventPaymentPlan $paymentPlan): Collection
    {
        return $rows
            ->groupBy(fn (array $row): string => $this->bookingKey($row))
            ->values()
            ->map(function (Collection $bookingRows) use ($temporaryPassword, $event, $discounts, $paymentPlan): Booking {
                return $this->importBooking($bookingRows->values(), $temporaryPassword, $event, $discounts, $paymentPlan);
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array{early: Discount, siblings: Discount}  $discounts
     */
    private function importBooking(Collection $rows, string $temporaryPassword, Event $event, array $discounts, EventPaymentPlan $paymentPlan): Booking
    {
        $firstRow = $rows->first();

        if (! is_array($firstRow)) {
            throw new RuntimeException('Cannot import an empty booking group.');
        }

        $sendWebhook = $rows->contains(fn (array $row): bool => $row['send_webhook'] ?? false);
        $customer = $this->upsertCustomer($firstRow, $temporaryPassword);
        $familyMembers = $rows->map(fn (array $row): FamilyMember => $this->upsertFamilyMember($customer, $row));
        $booking = $this->upsertBooking($customer, $event, $rows, $discounts);

        $familyMembers->each(function (FamilyMember $familyMember) use ($booking): void {
            $bookingFamilyMember = $booking->familyMembers()->firstOrNew([
                'family_member_id' => $familyMember->id,
            ]);

            $bookingFamilyMember->forceFill([
                'created_at' => $booking->created_at,
                'updated_at' => $booking->created_at,
            ])->save();

            $this->upsertContract($bookingFamilyMember);
        });

        $this->upsertScheduleAndPayments($booking->refresh(), $rows, $paymentPlan);

        $booking = $booking->refresh()->load(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract']);
        $booking->setAttribute('send_webhook', $sendWebhook);

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function bookingKey(array $row): string
    {
        return implode('|', [
            $row['civil_id'],
            $row['phone_number'],
            Str::lower((string) ($row['email'] ?? '')),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertCustomer(array $row, string $temporaryPassword): Customer
    {
        $addressParts = $this->addressParts((string) $row['address']);
        $customer = Customer::query()->firstOrNew(['phone_number' => $row['phone_number']]);

        $customer->forceFill([
            'name' => $row['guardian_name'],
            'email' => $row['email'],
            'civil_id' => $row['civil_id'],
            'address' => $row['address'],
            'wilaya' => $addressParts['wilaya'],
            'area' => $addressParts['area'],
            'password' => $temporaryPassword,
            'additional_info' => [
                ...($customer->additional_info ?? []),
                'import_source' => self::SourceName,
            ],
        ]);

        if (! $customer->exists) {
            $customer->forceFill([
                'created_at' => $row['registered_at'],
            ]);
        }

        $customer->forceFill([
            'updated_at' => $row['registered_at'],
        ])->save();

        return $customer->refresh();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertFamilyMember(Customer $customer, array $row): FamilyMember
    {
        $birthDate = $row['birth_date']->toDateString();
        $familyMember = FamilyMember::query()
            ->whereBelongsTo($customer)
            ->where('name', $row['student_name'])
            ->get()
            ->first(fn (FamilyMember $familyMember): bool => $familyMember->birth_date->toDateString() === $birthDate)
            ?? new FamilyMember([
                'customer_id' => $customer->id,
                'name' => $row['student_name'],
                'birth_date' => $birthDate,
            ]);

        $familyMember->forceFill([
            'grade' => $row['grade'],
            'relationship_to_customer' => $row['relationship'],
        ]);

        if (! $familyMember->exists) {
            $familyMember->forceFill([
                'created_at' => $row['registered_at'],
            ]);
        }

        $familyMember->forceFill([
            'updated_at' => $row['registered_at'],
        ])->save();

        return $familyMember->refresh();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array{early: Discount, siblings: Discount}  $discounts
     */
    private function upsertBooking(Customer $customer, Event $event, Collection $rows, array $discounts): Booking
    {
        $registeredAt = $this->registeredAt($rows);
        $familyMemberCount = $rows->count();

        if (! in_array($familyMemberCount, [1, 2], true)) {
            throw new InvalidArgumentException('A grouped booking must contain exactly one or two family members.');
        }

        $subtotalBaisa = $event->price_baisa * $familyMemberCount;
        $totalBaisa = (int) $rows->sum('amount_baisa');
        $discountAmountBaisa = max(0, $subtotalBaisa - $totalBaisa);
        $discount = $familyMemberCount === 1 ? $discounts['early'] : $discounts['siblings'];
        $reference = $this->bookingReference($rows);
        $booking = Booking::query()->firstOrNew(['reference' => $reference]);

        $booking->forceFill([
            'customer_id' => $customer->id,
            'event_id' => $event->id,
            'state' => Approved::$name,
            'reviewed_by_user_id' => null,
            'reviewed_at' => $registeredAt,
            'review_notes' => 'Imported from migrants 2026 workbook.',
            'unit_price_baisa' => $event->price_baisa,
            'currency' => $event->currency,
            'family_member_count' => $familyMemberCount,
            'subtotal_baisa' => $subtotalBaisa,
            'discount_id' => $discount->id,
            'discount_name' => $discount->name,
            'discount_amount_baisa' => $discountAmountBaisa,
            'total_baisa' => $totalBaisa,
            'created_at' => $registeredAt,
            'updated_at' => $registeredAt,
        ])->save();

        return $booking->refresh();
    }

    private function upsertContract(BookingFamilyMember $bookingFamilyMember): void
    {
        $bookingFamilyMember->loadMissing(['booking.customer', 'booking.event', 'familyMember', 'contract']);

        $contract = $bookingFamilyMember->contract()->firstOrNew();

        if ($contract->signed_at !== null) {
            return;
        }

        $contract->forceFill([
            'contract_html' => $this->contractRenderer->html($bookingFamilyMember),
            'created_at' => $bookingFamilyMember->booking->created_at,
            'updated_at' => $bookingFamilyMember->booking->created_at,
        ])->save();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function upsertScheduleAndPayments(Booking $booking, Collection $rows, EventPaymentPlan $paymentPlan): void
    {
        $status = $this->bookingStatus($rows);

        if ($status === self::FullyPaid) {
            $schedule = $this->upsertFullPaymentSchedule($booking, $rows);
            $installment = $this->upsertInstallment(
                $schedule,
                name: __('ui.payments.full_payment'),
                sequence: 1,
                percentage: 100,
                dueDate: $this->registeredAt($rows)->toDateString(),
                grossAmountBaisa: $booking->subtotal_baisa,
                discountAmountBaisa: $booking->discount_amount_baisa,
                amountBaisa: $booking->total_baisa,
                state: BookingInstallmentState::Paid,
                paidAt: $this->registeredAt($rows),
            );

            $this->upsertPaidPayment($installment, $rows);

            return;
        }

        $schedule = $this->upsertInstallmentSchedule($booking, $paymentPlan, $rows);
        $grossAmounts = $this->allocatedAmounts($booking->subtotal_baisa, [50, 50]);
        $discountAmounts = $this->allocatedAmounts($booking->discount_amount_baisa, [50, 50]);
        $amounts = [
            $grossAmounts[0] - $discountAmounts[0],
            $grossAmounts[1] - $discountAmounts[1],
        ];
        $firstInstallmentIsPaid = $status === self::FirstInstallmentPaid;

        $firstInstallment = $this->upsertInstallment(
            $schedule,
            name: 'القسط الأول',
            sequence: 1,
            percentage: 50,
            dueDate: Date::today()->toDateString(),
            grossAmountBaisa: $grossAmounts[0],
            discountAmountBaisa: $discountAmounts[0],
            amountBaisa: $amounts[0],
            state: $firstInstallmentIsPaid ? BookingInstallmentState::Paid : BookingInstallmentState::Pending,
            paidAt: $firstInstallmentIsPaid ? $this->registeredAt($rows) : null,
        );

        $this->upsertInstallment(
            $schedule,
            name: 'القسط الثاني',
            sequence: 2,
            percentage: 50,
            dueDate: '2026-07-27',
            grossAmountBaisa: $grossAmounts[1],
            discountAmountBaisa: $discountAmounts[1],
            amountBaisa: $amounts[1],
            state: BookingInstallmentState::Pending,
            paidAt: null,
        );

        if ($firstInstallmentIsPaid) {
            $this->upsertPaidPayment($firstInstallment, $rows);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function upsertFullPaymentSchedule(Booking $booking, Collection $rows): BookingPaymentSchedule
    {
        return $this->upsertSchedule($booking, [
            'event_payment_plan_id' => null,
            'plan_name' => __('ui.payments.full_payment'),
            'currency' => $booking->currency,
            'subtotal_baisa' => $booking->subtotal_baisa,
            'discount_amount_baisa' => $booking->discount_amount_baisa,
            'total_baisa' => $booking->total_baisa,
            'created_at' => $this->registeredAt($rows),
            'updated_at' => $this->registeredAt($rows),
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function upsertInstallmentSchedule(Booking $booking, EventPaymentPlan $paymentPlan, Collection $rows): BookingPaymentSchedule
    {
        return $this->upsertSchedule($booking, [
            'event_payment_plan_id' => $paymentPlan->id,
            'plan_name' => $paymentPlan->name,
            'currency' => $booking->currency,
            'subtotal_baisa' => $booking->subtotal_baisa,
            'discount_amount_baisa' => $booking->discount_amount_baisa,
            'total_baisa' => $booking->total_baisa,
            'created_at' => $this->registeredAt($rows),
            'updated_at' => $this->registeredAt($rows),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertSchedule(Booking $booking, array $attributes): BookingPaymentSchedule
    {
        $schedule = $booking->paymentSchedule()->firstOrNew();
        $schedule->forceFill($attributes)->save();

        return $schedule->refresh();
    }

    private function upsertInstallment(
        BookingPaymentSchedule $schedule,
        string $name,
        int $sequence,
        int $percentage,
        string $dueDate,
        int $grossAmountBaisa,
        int $discountAmountBaisa,
        int $amountBaisa,
        BookingInstallmentState $state,
        ?CarbonImmutable $paidAt,
    ): BookingInstallment {
        $installment = $schedule->installments()->firstOrNew(['sequence' => $sequence]);

        $installment->forceFill([
            'name' => $name,
            'percentage' => $percentage,
            'due_date' => $dueDate,
            'gross_amount_baisa' => $grossAmountBaisa,
            'discount_amount_baisa' => $discountAmountBaisa,
            'amount_baisa' => $amountBaisa,
            'currency' => $schedule->currency,
            'state' => $state,
            'paid_at' => $paidAt,
            'created_at' => $schedule->created_at,
            'updated_at' => $schedule->created_at,
        ])->save();

        return $installment->refresh();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function upsertPaidPayment(BookingInstallment $installment, Collection $rows): Payment
    {
        $paidAt = $this->registeredAt($rows);
        $reference = "PAY-{$installment->paymentSchedule->booking->reference}-S{$installment->sequence}";
        $payment = Payment::query()->firstOrNew(['reference' => $reference]);

        $payment->forceFill([
            'booking_installment_id' => $installment->id,
            'provider' => PaymentProvider::Thawani,
            'amount_baisa' => $installment->amount_baisa,
            'currency' => $installment->currency,
            'state' => PaymentState::Paid,
            'provider_session_id' => null,
            'provider_payment_id' => "manual-{$reference}",
            'provider_invoice' => null,
            'provider_payment_status' => 'paid',
            'checkout_url' => null,
            'request_payload' => null,
            'response_payload' => [
                'source' => self::SourceName,
                'row_numbers' => $rows->pluck('row_number')->values()->all(),
            ],
            'verified_at' => $paidAt,
            'paid_at' => $paidAt,
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ])->save();

        $installment->forceFill([
            'state' => BookingInstallmentState::Paid,
            'paid_at' => $paidAt,
        ])->save();

        $this->postPaymentLedgerTransaction->execute($payment->refresh());
        $this->reserveBookingSeats->execute($payment->refresh());

        return $payment->refresh();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function bookingReference(Collection $rows): string
    {
        return 'BRH-MIG26-'.$rows
            ->pluck('row_number')
            ->map(fn (int $rowNumber): string => str_pad((string) $rowNumber, 2, '0', STR_PAD_LEFT))
            ->implode('-');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function bookingStatus(Collection $rows): string
    {
        $statuses = $rows->pluck('status')->unique()->values();

        if ($statuses->count() !== 1) {
            throw new InvalidArgumentException('A grouped booking contains mixed payment statuses.');
        }

        return (string) $statuses->first();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function registeredAt(Collection $rows): CarbonImmutable
    {
        return $rows
            ->pluck('registered_at')
            ->sortBy(fn (CarbonImmutable $date): int => $date->getTimestamp())
            ->first();
    }

    /**
     * @param  array<int, int>  $percentages
     * @return array<int, int>
     */
    private function allocatedAmounts(int $amountBaisa, array $percentages): array
    {
        $allocated = [];
        $allocatedTotal = 0;

        foreach ($percentages as $index => $percentage) {
            if ($index === array_key_last($percentages)) {
                $allocated[] = $amountBaisa - $allocatedTotal;

                continue;
            }

            $amount = intdiv($amountBaisa * $percentage, 100);
            $allocated[] = $amount;
            $allocatedTotal += $amount;
        }

        return $allocated;
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function sendApprovalWebhooks(Collection $bookings): int
    {
        $bookings = $bookings->filter(fn (Booking $booking): bool => $booking->send_webhook ?? false);

        if ($bookings->isEmpty()) {
            return 0;
        }

        $bookingIds = $bookings->pluck('id');
        $existingDeliveryCount = WebhookDelivery::query()
            ->where('event', 'booking.created')
            ->where('webhookable_type', (new Booking)->getMorphClass())
            ->whereIn('webhookable_id', $bookingIds)
            ->count();

        $bookings->each(fn (Booking $booking): null => $this->sendApprovalWebhook($booking));

        $newDeliveryCount = WebhookDelivery::query()
            ->where('event', 'booking.created')
            ->where('webhookable_type', (new Booking)->getMorphClass())
            ->whereIn('webhookable_id', $bookingIds)
            ->count();

        return $newDeliveryCount - $existingDeliveryCount;
    }

    private function sendApprovalWebhook(Booking $booking): null
    {
        $this->webhookSender->sendBookingCreated($booking);

        return null;
    }

    /**
     * @return array{wilaya: string, area: string}
     */
    private function addressParts(string $address): array
    {
        $cleanAddress = $this->string($address);

        if (preg_match('/ولاية\s+(?<wilaya>[^\s\/،.]+)/u', $cleanAddress, $matches)) {
            return [
                'wilaya' => $matches['wilaya'],
                'area' => $cleanAddress,
            ];
        }

        $parts = collect(preg_split('/[\/،,.\-–]+/u', $cleanAddress) ?: [])
            ->map(fn (string $part): string => $this->string($part))
            ->filter()
            ->values();

        if ($parts->count() >= 2) {
            return [
                'wilaya' => (string) $parts->get(1),
                'area' => (string) $parts->last(),
            ];
        }

        return [
            'wilaya' => $cleanAddress,
            'area' => $cleanAddress,
        ];
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function cell(array $values, array $headers, string $header): mixed
    {
        if (! array_key_exists($header, $headers)) {
            return null;
        }

        return $values[$headers[$header]] ?? null;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function rowIsBlank(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->nullableString($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function requiredString(mixed $value, int $rowNumber, string $header): string
    {
        $string = $this->nullableString($value);

        if ($string === null) {
            throw new InvalidArgumentException("Row {$rowNumber} is missing [{$header}].");
        }

        return $string;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = $this->string($value);

        return $string === '' ? null : $string;
    }

    private function string(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->toDateString();
        }

        if (is_float($value)) {
            $value = fmod($value, 1.0) === 0.0
                ? number_format($value, 0, '', '')
                : rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
        }

        $string = str_replace("\u{FEFF}", '', (string) ($value ?? ''));

        return trim(preg_replace('/\s+/u', ' ', $string) ?? $string);
    }

    private function email(mixed $value): ?string
    {
        $email = $this->nullableString($value);

        return $email === null ? null : Str::lower($email);
    }

    private function parseSendWebhookFlag(mixed $value): bool
    {
        $string = $this->nullableString($value);

        return $string !== null && in_array(strtolower($string), ['1', 'true', 'yes', 'y'], true);
    }

    private function date(mixed $value, int $rowNumber, string $header): CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $value);
        }

        try {
            return CarbonImmutable::parse($this->requiredString($value, $rowNumber, $header))->startOfDay();
        } catch (\Throwable) {
            throw new InvalidArgumentException("Row {$rowNumber} has an invalid [{$header}] date.");
        }
    }

    private function money(mixed $value, int $rowNumber): int
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Row {$rowNumber} has an invalid payment amount.");
        }

        return (int) round(((float) $value) * 1000);
    }
}
