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
        $timestamp = now();

        DB::table('events')->insertOrIgnore([
            'name' => 'رحلة العمرة بصحبة أبي بلج',
            'slug' => 'umrah-2026',
            'type' => 'trip',
            'status' => 'published',
            'landing_page_key' => 'umrah-2026-v1',
            'excerpt' => 'عشرة أيام تبدأ بثلاثة أيام تهيئة في مخيم بيرحاء بإبراء، ثم رحلة برية إلى مكة المكرمة بصحبة تربوية وإشراف منظم.',
            'description_html' => <<<'HTML'
<p>رحلة عمرة تربوية للفتيان في الصفوف العاشر والحادي عشر والثاني عشر. تبدأ بثلاثة أيام في مخيم بيرحاء بإبراء لتعلّم المناسك، والتعارف مع المجموعة، والاستعداد للسفر، ثم تنطلق برًا إلى مكة المكرمة.</p>
<h2>المسار العام</h2>
<ul>
    <li>٢٠ إلى ٢٢ أغسطس: تهيئة عملية وإقامة في مخيم بيرحاء.</li>
    <li>٢٣ إلى ٢٤ أغسطس: السفر والوصول إلى مكة المكرمة.</li>
    <li>٢٥ إلى ٢٧ أغسطس: العمرة والصلوات والبرنامج التربوي في مكة.</li>
    <li>٢٨ إلى ٢٩ أغسطس: العودة واللقاء الختامي في إبراء.</li>
</ul>
<p>تُسلّم أوقات التجمع والوصول التفصيلية لولي الأمر قبل الانطلاق بعد اعتماد ترتيبات الطريق والمنافذ.</p>
HTML,
            'contract_terms_html' => <<<'HTML'
<h2>الشروط الأساسية</h2>
<ul>
    <li>الرحلة مخصصة للفتيان من ١٦ إلى ١٨ سنة.</li>
    <li>يلزم جواز سفر ساري المفعول وموافقة خطية من ولي الأمر.</li>
    <li>يعبئ ولي الأمر المعلومات الصحية والتعليمات الخاصة قبل توقيع العقد.</li>
    <li>قد تتغير أوقات الطريق والوصول وفق إجراءات المنافذ وحالة الطريق، وتبلغ الإدارة ولي الأمر بأي تغيير معتمد.</li>
    <li>توضح تفاصيل ما يشمله السعر وسياسة الإلغاء في العقد النهائي قبل الدفع.</li>
</ul>
HTML,
            'participant_extra_fields' => '[]',
            'location' => 'مخيم بيرحاء في إبراء، ثم مكة المكرمة',
            'starts_at' => '2026-08-20 00:00:00',
            'ends_at' => '2026-08-29 23:59:59',
            'minimum_age' => 16,
            'maximum_age' => 18,
            'seat_capacity' => 30,
            'price_baisa' => 460000,
            'currency' => 'OMR',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'umrah-2026')
            ->value('id');

        if ($eventId === null || DB::table('bookings')->where('event_id', $eventId)->exists()) {
            return;
        }

        DB::table('events')->where('id', $eventId)->delete();
    }
};
