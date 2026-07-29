<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{name: string, position: int, seat_capacity: int, price_baisa: int}>
     */
    private const PRICE_TIERS = [
        ['name' => 'الباكورة', 'position' => 1, 'seat_capacity' => 8, 'price_baisa' => 380000],
        ['name' => 'المتقدمة', 'position' => 2, 'seat_capacity' => 12, 'price_baisa' => 420000],
        ['name' => 'الختامية', 'position' => 3, 'seat_capacity' => 10, 'price_baisa' => 460000],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $event = DB::table('events')
            ->where('slug', 'umrah-2026')
            ->first(['id', 'currency']);

        if ($event === null) {
            return;
        }

        $tierIds = DB::table('event_price_tiers')
            ->where('event_id', $event->id)
            ->pluck('id');

        $hasSeatAllocations = $tierIds->isNotEmpty()
            && DB::table('booking_seat_allocations')
                ->whereIn('event_price_tier_id', $tierIds)
                ->exists();

        if ($hasSeatAllocations) {
            throw new RuntimeException('Cannot replace Umrah price tiers after seats have been allocated.');
        }

        DB::transaction(function () use ($event): void {
            DB::table('event_price_tiers')
                ->where('event_id', $event->id)
                ->delete();

            $timestamp = now();
            $currency = is_string($event->currency) ? $event->currency : 'OMR';

            DB::table('event_price_tiers')->insert(array_map(
                fn (array $tier): array => [
                    'event_id' => $event->id,
                    ...$tier,
                    'currency' => $currency,
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                self::PRICE_TIERS,
            ));

            DB::table('events')
                ->where('id', $event->id)
                ->update([
                    'price_baisa' => 460000,
                    'updated_at' => $timestamp,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'umrah-2026')
            ->value('id');

        if ($eventId === null) {
            return;
        }

        DB::table('event_price_tiers')
            ->where('event_id', $eventId)
            ->whereIn('name', array_column(self::PRICE_TIERS, 'name'))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('booking_seat_allocations')
                ->whereColumn('booking_seat_allocations.event_price_tier_id', 'event_price_tiers.id'))
            ->delete();
    }
};
