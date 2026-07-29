<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('bookings')
            ->orderBy('id')
            ->chunkById(100, function ($bookings): void {
                foreach ($bookings as $booking) {
                    if (in_array($booking->state, ['cancelled', 'rejected'], true)) {
                        continue;
                    }

                    $payment = DB::table('payments')
                        ->join('booking_installments', 'booking_installments.id', '=', 'payments.booking_installment_id')
                        ->join('booking_payment_schedules', 'booking_payment_schedules.id', '=', 'booking_installments.booking_payment_schedule_id')
                        ->where('booking_payment_schedules.booking_id', $booking->id)
                        ->whereIn('payments.state', ['paid', 'partially_refunded'])
                        ->orderByRaw('COALESCE(payments.paid_at, payments.updated_at)')
                        ->orderBy('payments.id')
                        ->select(['payments.id', 'payments.paid_at', 'payments.updated_at'])
                        ->first();

                    if ($payment === null) {
                        continue;
                    }

                    $seatCount = DB::table('booking_family_members')
                        ->where('booking_id', $booking->id)
                        ->count();

                    if ($seatCount < 1) {
                        continue;
                    }

                    $reservedAt = $payment->paid_at ?? $payment->updated_at;

                    DB::table('booking_seat_allocations')->insert([
                        'booking_id' => $booking->id,
                        'event_id' => $booking->event_id,
                        'payment_id' => $payment->id,
                        'seat_count' => $seatCount,
                        'state' => 'reserved',
                        'reserved_at' => $reservedAt,
                        'created_at' => $reservedAt,
                        'updated_at' => $reservedAt,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('booking_seat_allocations')->delete();
    }
};
