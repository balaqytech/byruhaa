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
        DB::table('events')->insertOrIgnore([
            'name' => 'ربانيين',
            'subtitle' => 'برنامج بِيرُحاء لحفظ القرآن للفتيان',
            'slug' => 'rabbaniyeen',
            'type' => 'camp',
            'status' => 'published',
            'enrollment_status' => 'interest_open',
            'landing_page_key' => 'rabbaniyeen-v1',
            'excerpt' => 'برنامج حفظ منظَّم للفتيان من الصف السابع إلى الثاني عشر: وِرد يومي عن بُعد، وليلتان في بِيرُحاء كل شهر. سجّل اهتمامك بلا التزام.',
            'description_html' => '<p>برنامج ربانيين يجمع وِرد الحفظ اليومي عن بُعد بلقاء حضوري في بيرحاء كل شهر. يبدأ كل فتى بمسار السكينة أربعة أسابيع، ثم يتدرج بحسب قدرته بعد اختبار التلاوة.</p>',
            'location' => 'مخيم بِيرُحاء، إبراء، سلطنة عُمان',
            'schedule_text' => 'الانطلاقة المستهدفة: يناير ٢٠٢٧م',
            'starts_at' => null,
            'ends_at' => null,
            'minimum_age' => 11,
            'maximum_age' => 19,
            'seat_capacity' => 0,
            'price_baisa' => 0,
            'currency' => 'OMR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $event = DB::table('events')->where('slug', 'rabbaniyeen')->first(['id', 'landing_page_key']);

        if ($event === null || $event->landing_page_key !== 'rabbaniyeen-v1') {
            return;
        }

        if (DB::table('event_interest_leads')->where('event_id', $event->id)->exists()
            || DB::table('bookings')->where('event_id', $event->id)->exists()) {
            return;
        }

        DB::table('events')->where('id', $event->id)->delete();
    }
};
