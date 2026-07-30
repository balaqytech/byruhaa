<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{name: string, position: int, seat_capacity: int, price_baisa: int}>
     */
    private const PRICE_TIERS = [
        ['name' => 'الباكورة الأولى', 'position' => 1, 'seat_capacity' => 10, 'price_baisa' => 59000],
        ['name' => 'الباكورة الثانية', 'position' => 2, 'seat_capacity' => 10, 'price_baisa' => 69000],
        ['name' => 'الباكورة الثالثة', 'position' => 3, 'seat_capacity' => 10, 'price_baisa' => 75000],
        ['name' => 'الباكورة الرابعة', 'position' => 4, 'seat_capacity' => 10, 'price_baisa' => 79000],
        ['name' => 'الباكورة الخامسة', 'position' => 5, 'seat_capacity' => 10, 'price_baisa' => 89000],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $timestamp = now();

            DB::table('events')->insertOrIgnore([
                'name' => 'بعد الثاني عشر، الطريق يبدأ',
                'slug' => 'after-twelfth-2026',
                'type' => 'camp',
                'status' => 'published',
                'landing_page_key' => 'life-after-school-v1',
                'excerpt' => 'ثلاثة أيام لخريجي الصف الثاني عشر تساعد الفتى على اكتشاف ميوله، وفهم مساراته، والخروج بخطة تسعين يومًا مكتوبة بيده.',
                'description_html' => <<<'HTML'
<p>فعالية إقامية مركزة لخريجي الصف الثاني عشر من الفتيان. لا نلقّن المشارك ماذا يختار، بل نساعده على اكتشاف ذاته وميوله وفهم المسارات الجامعية والتقنية والمهنية والريادية والتعلّم الذاتي.</p>
<p>يمر المشارك بمحطات عملية في مهارات المستقبل وإعادة تعريف التعثر، ثم يغادر بخريطة مسار وخطة تسعين يومًا مكتوبة بيده تبدأ من اليوم التالي.</p>
HTML,
                'contract_terms_html' => <<<'HTML'
<h2>الشروط الأساسية</h2>
<ul>
    <li>الفعالية مخصصة لخريجي الصف الثاني عشر من الفتيان بعمر 17 إلى 18 سنة.</li>
    <li>تقام الفعالية في مخيم بيرحاء بولاية إبراء، ويزوّد ولي الأمر بتفاصيل الوصول والتجهيز قبل بدايتها.</li>
    <li>يجب تعبئة بيانات المشارك والمعلومات الصحية والتعليمات الخاصة بدقة قبل توقيع العقد.</li>
    <li>يصبح المقعد محجوزًا بعد نجاح دفع المبلغ كاملًا أو دفع القسط المستحق، ويبقى متاحًا قبل ذلك.</li>
    <li>تظهر سياسة الإلغاء والاسترداد وما يشمله السعر في العقد النهائي قبل الدفع.</li>
</ul>
HTML,
                'participant_extra_fields' => '[]',
                'location' => 'مخيم بيرحاء، إبراء، سلطنة عُمان',
                'starts_at' => '2026-08-13 12:00:00',
                'ends_at' => null,
                'minimum_age' => 17,
                'maximum_age' => 18,
                'seat_capacity' => 50,
                'price_baisa' => 89000,
                'currency' => 'OMR',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $event = DB::table('events')
                ->where('slug', 'after-twelfth-2026')
                ->first(['id', 'landing_page_key']);

            if ($event === null) {
                return;
            }

            if ($event->landing_page_key === null) {
                DB::table('events')
                    ->where('id', $event->id)
                    ->update([
                        'landing_page_key' => 'life-after-school-v1',
                        'updated_at' => $timestamp,
                    ]);
            }

            if (DB::table('event_price_tiers')->where('event_id', $event->id)->exists()) {
                return;
            }

            DB::table('event_price_tiers')->insert(array_map(
                fn (array $tier): array => [
                    'event_id' => $event->id,
                    ...$tier,
                    'currency' => 'OMR',
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                self::PRICE_TIERS,
            ));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'after-twelfth-2026')
            ->value('id');

        if ($eventId === null || DB::table('bookings')->where('event_id', $eventId)->exists()) {
            return;
        }

        DB::table('events')->where('id', $eventId)->delete();
    }
};
