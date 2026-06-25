<?php

namespace App\Support;

use App\Models\BookingFamilyMember;
use App\Models\EventContract;
use Carbon\CarbonInterface;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\HtmlString;

class ContractVariables
{
    private const MissingValue = '—';

    /**
     * @return array<string, array<string, string>>
     */
    public static function definitions(): array
    {
        return [
            __('admin.contract_variables.groups.guardian') => [
                'guardian_name' => __('admin.contract_variables.labels.guardian_name'),
                'guardian_civil_id' => __('admin.contract_variables.labels.guardian_civil_id'),
                'guardian_relationship' => __('admin.contract_variables.labels.guardian_relationship'),
                'guardian_phone' => __('admin.contract_variables.labels.guardian_phone'),
                'guardian_wilaya' => __('admin.contract_variables.labels.guardian_wilaya'),
                'guardian_area' => __('admin.contract_variables.labels.guardian_area'),
                'guardian_address' => __('admin.contract_variables.labels.guardian_address'),
            ],
            __('admin.contract_variables.groups.student') => [
                'student_name' => __('admin.contract_variables.labels.student_name'),
                'student_birth_date' => __('admin.contract_variables.labels.student_birth_date'),
                'student_age' => __('admin.contract_variables.labels.student_age'),
                'student_grade' => __('admin.contract_variables.labels.student_grade'),
                'student_school' => __('admin.contract_variables.labels.student_school'),
            ],
            __('admin.contract_variables.groups.event') => [
                'event_name' => __('admin.contract_variables.labels.event_name'),
                'event_location' => __('admin.contract_variables.labels.event_location'),
                'event_start_date' => __('admin.contract_variables.labels.event_start_date'),
                'event_end_date' => __('admin.contract_variables.labels.event_end_date'),
                'event_duration' => __('admin.contract_variables.labels.event_duration'),
                'event_year' => __('admin.contract_variables.labels.event_year'),
                'event_price' => __('admin.contract_variables.labels.event_price'),
                'event_currency' => __('admin.contract_variables.labels.event_currency'),
            ],
            __('admin.contract_variables.groups.booking') => [
                'booking_reference' => __('admin.contract_variables.labels.booking_reference'),
                'booking_date' => __('admin.contract_variables.labels.booking_date'),
                'agreed_fee' => __('admin.contract_variables.labels.agreed_fee'),
                'discount_name' => __('admin.contract_variables.labels.discount_name'),
                'subtotal' => __('admin.contract_variables.labels.subtotal'),
                'discount_amount' => __('admin.contract_variables.labels.discount_amount'),
                'total_amount' => __('admin.contract_variables.labels.total_amount'),
            ],
            __('admin.contract_variables.groups.contract') => [
                'contract_date' => __('admin.contract_variables.labels.contract_date'),
                'contract_signed_name' => __('admin.contract_variables.labels.contract_signed_name'),
                'contract_signed_at' => __('admin.contract_variables.labels.contract_signed_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mergeTagLabels(): array
    {
        $labels = [];

        foreach (self::definitions() as $variables) {
            foreach ($variables as $key => $label) {
                $labels[$key] = $label;
            }
        }

        return $labels;
    }

    public static function render(string|array|null $content, EventContract|BookingFamilyMember $source): string
    {
        $values = self::values($source);
        $html = self::renderRichEditorContent($content, $values);

        return preg_replace_callback('/\{\{\s*(?<key>[A-Za-z0-9_]+)\s*\}\}/', function (array $matches) use ($values): string {
            return e($values[$matches['key']] ?? self::MissingValue);
        }, $html) ?? $html;
    }

    /**
     * @param  array<string, string>  $values
     */
    private static function renderRichEditorContent(string|array|null $content, array $values): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        if (is_array($content)) {
            return RichContentRenderer::make($content)
                ->mergeTags(self::htmlMergeTagValues($values))
                ->toUnsafeHtml();
        }

        $decodedContent = json_decode($content, associative: true);

        if (
            is_array($decodedContent)
            && json_last_error() === JSON_ERROR_NONE
            && ($decodedContent['type'] ?? null) === 'doc'
        ) {
            return RichContentRenderer::make($decodedContent)
                ->mergeTags(self::htmlMergeTagValues($values))
                ->toUnsafeHtml();
        }

        if (str_contains($content, 'data-type="mergeTag"') || str_contains($content, "data-type='mergeTag'")) {
            return RichContentRenderer::make($content)
                ->mergeTags(self::htmlMergeTagValues($values))
                ->toUnsafeHtml();
        }

        return $content;
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, HtmlString>
     */
    private static function htmlMergeTagValues(array $values): array
    {
        return array_map(
            fn (string $value): HtmlString => new HtmlString(e($value)),
            $values,
        );
    }

    /**
     * @return array<string, string>
     */
    public static function values(EventContract|BookingFamilyMember $source): array
    {
        $contract = $source instanceof EventContract ? $source : null;
        $bookingFamilyMember = $source instanceof EventContract
            ? $source->loadMissing('bookingFamilyMember.booking.customer', 'bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember')->bookingFamilyMember
            : $source;

        $bookingFamilyMember->loadMissing(['booking.customer', 'booking.event', 'booking.familyMembers', 'familyMember']);

        $booking = $bookingFamilyMember->booking;
        $customer = $booking->customer;
        $event = $booking->event;
        $familyMember = $bookingFamilyMember->familyMember;
        $eventDate = $event->starts_at ?? now();
        $amounts = self::perMemberAmounts($bookingFamilyMember);

        return [
            'guardian_name' => self::value($customer->name),
            'guardian_civil_id' => self::value($customer->civil_id),
            'guardian_relationship' => self::value($familyMember->relationship_to_customer),
            'guardian_phone' => self::value($customer->phone_number),
            'guardian_wilaya' => self::value($customer->wilaya),
            'guardian_area' => self::value($customer->area),
            'guardian_address' => self::value($customer->address),

            'student_name' => self::value($familyMember->name),
            'student_birth_date' => self::date($familyMember->birth_date),
            'student_age' => self::value((string) $familyMember->ageAt($eventDate)),
            'student_grade' => self::value($familyMember->grade),
            'student_school' => self::value($familyMember->school_name),

            'event_name' => self::value($event->name),
            'event_location' => self::value($event->location),
            'event_start_date' => self::dateTime($event->starts_at),
            'event_end_date' => self::dateTime($event->ends_at),
            'event_duration' => self::duration($event->starts_at, $event->ends_at),
            'event_year' => self::value($event->starts_at?->format('Y') ?? $event->ends_at?->format('Y')),
            'event_price' => MoneyFormatter::baisa($event->price_baisa, $event->currency),
            'event_currency' => self::value($event->currency),

            'booking_reference' => self::value($booking->reference),
            'booking_date' => self::date($booking->created_at),
            'agreed_fee' => MoneyFormatter::baisa($amounts['total_baisa'], $booking->currency),
            'discount_name' => self::value($booking->discount_name),
            'subtotal' => MoneyFormatter::baisa($amounts['subtotal_baisa'], $booking->currency),
            'discount_amount' => MoneyFormatter::baisa($amounts['discount_amount_baisa'], $booking->currency),
            'total_amount' => MoneyFormatter::baisa($amounts['total_baisa'], $booking->currency),

            'contract_date' => self::date($contract?->created_at ?? now()),
            'contract_signed_name' => self::value($contract?->signed_name),
            'contract_signed_at' => self::dateTime($contract?->signed_at),
        ];
    }

    /**
     * @return array{subtotal_baisa: int, discount_amount_baisa: int, total_baisa: int}
     */
    private static function perMemberAmounts(BookingFamilyMember $bookingFamilyMember): array
    {
        $booking = $bookingFamilyMember->booking;
        $familyMembers = $booking->familyMembers->sortBy('id')->values();
        $familyMemberCount = max(1, $familyMembers->count(), $booking->family_member_count);
        $familyMemberIndex = $familyMembers->search(
            fn (BookingFamilyMember $member): bool => $member->is($bookingFamilyMember),
        );
        $familyMemberIndex = $familyMemberIndex === false ? 0 : (int) $familyMemberIndex;
        $subtotalBaisa = $booking->unit_price_baisa > 0
            ? $booking->unit_price_baisa
            : self::allocatedAmount($booking->subtotal_baisa, $familyMemberCount, $familyMemberIndex);

        return [
            'subtotal_baisa' => $subtotalBaisa,
            'discount_amount_baisa' => self::allocatedAmount($booking->discount_amount_baisa, $familyMemberCount, $familyMemberIndex),
            'total_baisa' => self::allocatedAmount($booking->total_baisa, $familyMemberCount, $familyMemberIndex),
        ];
    }

    private static function allocatedAmount(int $amountBaisa, int $parts, int $index): int
    {
        $amountBaisa = max(0, $amountBaisa);
        $parts = max(1, $parts);
        $index = min(max(0, $index), $parts - 1);
        $baseAmount = intdiv($amountBaisa, $parts);

        return $index === $parts - 1
            ? $baseAmount + ($amountBaisa % $parts)
            : $baseAmount;
    }

    private static function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return self::MissingValue;
        }

        return (string) $value;
    }

    private static function date(?CarbonInterface $date): string
    {
        return $date?->format('Y-m-d') ?? self::MissingValue;
    }

    private static function dateTime(?CarbonInterface $date): string
    {
        return $date?->format('Y-m-d H:i') ?? self::MissingValue;
    }

    private static function duration(?CarbonInterface $startsAt, ?CarbonInterface $endsAt): string
    {
        if (! $startsAt || ! $endsAt) {
            return self::MissingValue;
        }

        return $startsAt->diffForHumans($endsAt, [
            'parts' => 2,
            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
        ]);
    }
}
