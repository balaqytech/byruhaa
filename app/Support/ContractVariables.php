<?php

namespace App\Support;

use App\Models\BookingFamilyMember;
use App\Models\EventContract;
use Carbon\CarbonInterface;

class ContractVariables
{
    private const MissingValue = '—';

    /**
     * @return array<string, array<string, string>>
     */
    public static function definitions(): array
    {
        return [
            'Guardian' => [
                'guardian_name' => 'Guardian name',
                'guardian_civil_id' => 'Guardian civil ID',
                'guardian_relationship' => 'Guardian relationship',
                'guardian_phone' => 'Guardian phone',
                'guardian_wilaya' => 'Guardian wilaya',
                'guardian_area' => 'Guardian area',
            ],
            'Student' => [
                'student_name' => 'Student name',
                'student_birth_date' => 'Student birth date',
                'student_age' => 'Student age',
                'student_grade' => 'Student grade',
                'student_school' => 'Student school',
            ],
            'Event' => [
                'event_name' => 'Event name',
                'event_location' => 'Event location',
                'event_start_date' => 'Event start date',
                'event_end_date' => 'Event end date',
                'event_duration' => 'Event duration',
                'event_year' => 'Event year',
                'event_price' => 'Event price',
                'event_currency' => 'Event currency',
            ],
            'Booking' => [
                'booking_reference' => 'Booking reference',
                'booking_date' => 'Booking date',
                'agreed_fee' => 'Agreed fee',
                'discount_name' => 'Discount name',
                'subtotal' => 'Subtotal',
                'discount_amount' => 'Discount amount',
                'total_amount' => 'Total amount',
            ],
            'Contract' => [
                'contract_date' => 'Contract date',
                'contract_signed_name' => 'Contract signed name',
                'contract_signed_at' => 'Contract signed at',
            ],
        ];
    }

    public static function render(string $html, EventContract|BookingFamilyMember $source): string
    {
        $values = self::values($source);

        return preg_replace_callback('/\{\{\s*(?<key>[A-Za-z0-9_]+)\s*\}\}/', function (array $matches) use ($values): string {
            return e($values[$matches['key']] ?? self::MissingValue);
        }, $html) ?? $html;
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

        $bookingFamilyMember->loadMissing(['booking.customer', 'booking.event', 'familyMember']);

        $booking = $bookingFamilyMember->booking;
        $customer = $booking->customer;
        $event = $booking->event;
        $familyMember = $bookingFamilyMember->familyMember;
        $eventDate = $event->starts_at ?? now();

        return [
            'guardian_name' => self::value($customer->name),
            'guardian_civil_id' => self::value($customer->getAttribute('guardian_civil_id')),
            'guardian_relationship' => self::value($customer->getAttribute('guardian_relationship')),
            'guardian_phone' => self::value($customer->phone_number),
            'guardian_wilaya' => self::value($customer->getAttribute('guardian_wilaya')),
            'guardian_area' => self::value($customer->getAttribute('guardian_area')),

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
            'agreed_fee' => MoneyFormatter::baisa($booking->total_baisa, $booking->currency),
            'discount_name' => self::value($booking->discount_name),
            'subtotal' => MoneyFormatter::baisa($booking->subtotal_baisa, $booking->currency),
            'discount_amount' => MoneyFormatter::baisa($booking->discount_amount_baisa, $booking->currency),
            'total_amount' => MoneyFormatter::baisa($booking->total_baisa, $booking->currency),

            'contract_date' => self::date($contract?->created_at ?? now()),
            'contract_signed_name' => self::value($contract?->signed_name),
            'contract_signed_at' => self::dateTime($contract?->signed_at),
        ];
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
