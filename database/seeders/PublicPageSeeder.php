<?php

namespace Database\Seeders;

use App\Modules\Content\Enums\PublicPageStatus;
use App\Modules\Content\Models\PublicPage;
use Illuminate\Database\Seeder;

class PublicPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            PublicPage::query()->firstOrCreate(['key' => $page['key']], $page);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function pages(): array
    {
        return [
            [
                'key' => 'refund-cancellation',
                'title' => 'سياسة الإلغاء والاسترداد',
                'content' => '<h2>الإلغاء قبل الدفع</h2><p>يمكنك تعديل محتوى السلة أو مغادرة صفحة الدفع قبل إتمام العملية دون أي رسوم.</p><h2>بعد الدفع</h2><p>للاستفسار عن إلغاء الطلب أو استرداده، تواصل معنا عبر واتساب مع رقم الطلب. تتم مراجعة الطلب وفق حالته ووقت الاستلام.</p>',
                'status' => PublicPageStatus::Published,
                'published_at' => now(),
                'effective_at' => now(),
                'version' => 1,
                'meta_title' => 'سياسة الإلغاء والاسترداد | بِيرُحاء إبراء',
                'meta_description' => 'تعرف على سياسة الإلغاء والاسترداد لطلبات قهوة بيرحاء.',
            ],
            [
                'key' => 'privacy',
                'title' => 'سياسة الخصوصية',
                'content' => '<h2>ما الذي نحتاجه؟</h2><p>نستخدم بيانات التواصل ومعلومات الطلب لتنفيذ الطلب والرد على استفساراتك وتحسين الخدمة.</p><h2>حماية البيانات</h2><p>لا نبيع بياناتك، ونشارك الحد الأدنى اللازم مع مزودي الدفع والخدمات المرتبطة بإتمام الطلب.</p>',
                'status' => PublicPageStatus::Published,
                'published_at' => now(),
                'effective_at' => now(),
                'version' => 1,
                'meta_title' => 'سياسة الخصوصية | بِيرُحاء إبراء',
                'meta_description' => 'سياسة الخصوصية واستخدام البيانات في بِيرُحاء إبراء.',
            ],
            [
                'key' => 'terms',
                'title' => 'الشروط والأحكام',
                'content' => '<h2>استخدام المتجر</h2><p>باستخدام المتجر، تقر بصحة بيانات التواصل وتوافق على مراجعة تفاصيل الطلب قبل الدفع.</p><h2>الأسعار</h2><p>الأسعار المعروضة نهائية وتشمل ضريبة القيمة المضافة المطبقة.</p>',
                'status' => PublicPageStatus::Published,
                'published_at' => now(),
                'effective_at' => now(),
                'version' => 1,
                'meta_title' => 'الشروط والأحكام | بِيرُحاء إبراء',
                'meta_description' => 'الشروط والأحكام الخاصة بمتجر قهوة بيرحاء.',
            ],
            [
                'key' => 'pickup',
                'title' => 'سياسة الاستلام',
                'content' => '<h2>الاستلام من الموقع</h2><p>نجهز الطلب للاستلام من موقع قهوة بيرحاء في الوقت المحدد أو في أقرب وقت متاح حسب الخيار الذي اخترته.</p><h2>عند الوصول</h2><p>يرجى إبراز رقم الطلب أو رمز التتبع لمساعدتنا على تسليم الطلب بسرعة.</p>',
                'status' => PublicPageStatus::Published,
                'published_at' => now(),
                'effective_at' => now(),
                'version' => 1,
                'meta_title' => 'سياسة الاستلام | بِيرُحاء إبراء',
                'meta_description' => 'تعرف على طريقة ووقت استلام طلبات قهوة بيرحاء.',
            ],
            [
                'key' => 'student-accounts',
                'title' => 'حسابات الطلبة',
                'content' => '<p>هذه الصفحة قيد الإعداد. ستوضح لاحقًا خيارات الحسابات المخصصة للطلبة.</p>',
                'status' => PublicPageStatus::Draft,
                'published_at' => null,
                'effective_at' => null,
                'version' => 1,
                'meta_title' => 'حسابات الطلبة | بِيرُحاء إبراء',
                'meta_description' => null,
            ],
            [
                'key' => 'faq',
                'title' => 'الأسئلة الشائعة',
                'content' => '<p>هذه الصفحة قيد الإعداد.</p>',
                'status' => PublicPageStatus::Draft,
                'published_at' => null,
                'effective_at' => null,
                'version' => 1,
                'meta_title' => 'الأسئلة الشائعة | بِيرُحاء إبراء',
                'meta_description' => null,
            ],
            [
                'key' => 'allergens',
                'title' => 'مسببات الحساسية',
                'content' => '<p>هذه الصفحة قيد الإعداد وستتضمن معلومات المنتجات ومسببات الحساسية.</p>',
                'status' => PublicPageStatus::Draft,
                'published_at' => null,
                'effective_at' => null,
                'version' => 1,
                'meta_title' => 'مسببات الحساسية | بِيرُحاء إبراء',
                'meta_description' => null,
            ],
            [
                'key' => 'affiliate-terms',
                'title' => 'شروط التسويق بالعمولة',
                'content' => '<p>هذه الصفحة قيد الإعداد وستتضمن شروط برنامج التسويق بالعمولة.</p>',
                'status' => PublicPageStatus::Draft,
                'published_at' => null,
                'effective_at' => null,
                'version' => 1,
                'meta_title' => 'شروط التسويق بالعمولة | بِيرُحاء إبراء',
                'meta_description' => null,
            ],
        ];
    }
}
