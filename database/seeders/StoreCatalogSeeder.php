<?php

namespace Database\Seeders;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->categories() as $slug => $categoryData) {
                Category::query()->firstOrCreate(
                    ['slug' => $slug],
                    [...$categoryData, 'is_active' => true],
                );
            }

            ProductOption::withoutEvents(function (): void {
                foreach ($this->products() as $productData) {
                    $product = Product::query()->firstOrCreate(
                        ['slug' => $productData['slug']],
                        [
                            'category_id' => Category::query()->where('slug', $productData['category'])->value('id'),
                            'name' => $productData['name'],
                            'source_name' => $productData['source_name'],
                            'author_name' => $productData['author_name'],
                            'display_tag' => $productData['display_tag'],
                            'description' => $productData['description'],
                            'allergens' => $productData['allergens'],
                            'status' => ProductStatus::Active,
                            'sort_order' => $productData['sort_order'],
                            'is_featured' => false,
                            'featured_sort_order' => 0,
                        ],
                    );

                    if (! $product->wasRecentlyCreated) {
                        continue;
                    }

                    foreach ($productData['options'] as $optionIndex => $optionData) {
                        ProductOption::query()->create([
                            'product_id' => $product->getKey(),
                            'name' => $optionData['name'],
                            'sku' => $optionData['sku'],
                            'price_baisa' => $optionData['price_baisa'],
                            'currency' => 'OMR',
                            'is_available' => true,
                            'is_default' => $optionIndex === 0 ? true : null,
                            'tracks_inventory' => false,
                            'stock_on_hand' => 0,
                            'sort_order' => $optionIndex,
                        ]);
                    }
                }
            });
        });
    }

    /**
     * @return array<string, array{name: string, short_name: string, description: string, sort_order: int}>
     */
    private function categories(): array
    {
        return [
            'fresh' => ['name' => 'الموهيتو والمنعشات', 'short_name' => 'الموهيتو', 'description' => 'مشروبات فوارة وشاي مثلج بنكهات منعشة.', 'sort_order' => 10],
            'frozen' => ['name' => 'الجميدة والمثلجات', 'short_name' => 'المثلجات', 'description' => 'جميدة ومشروبات مثلجة باردة.', 'sort_order' => 20],
            'sweets' => ['name' => 'الحلويات', 'short_name' => 'الحلويات', 'description' => 'شرائح كعك وحلويات مختارة.', 'sort_order' => 30],
            'cold' => ['name' => 'القهوة الباردة', 'short_name' => 'قهوة باردة', 'description' => 'قهوة مختصة وماتشا تقدم باردة.', 'sort_order' => 40],
            'hot' => ['name' => 'القهوة الساخنة', 'short_name' => 'قهوة ساخنة', 'description' => 'إسبريسو وقهوة مختصة وماتشا ساخنة.', 'sort_order' => 50],
            'tools' => ['name' => 'الأدوات والهدايا', 'short_name' => 'أدوات وهدايا', 'description' => 'أدوات تحضير القهوة وهدايا بيرحاء.', 'sort_order' => 60],
            'antiques' => ['name' => 'التحف والمقتنيات', 'short_name' => 'المقتنيات', 'description' => 'قطع قديمة ومقتنيات محدودة.', 'sort_order' => 70],
            'books' => ['name' => 'الكتب', 'short_name' => 'الكتب', 'description' => 'كتب عربية مختارة للفتيان واليافعين.', 'sort_order' => 80],
        ];
    }

    /**
     * @return list<array{
     *     category: string,
     *     slug: string,
     *     name: string,
     *     source_name: string|null,
     *     author_name: string|null,
     *     display_tag: string,
     *     description: string,
     *     allergens: list<string>,
     *     sort_order: int,
     *     options: list<array{name: string, sku: string, price_baisa: int}>
     * }>
     */
    private function products(): array
    {
        $products = [
            ['category' => 'fresh', 'name' => 'المنعش الفوّار بالباشن', 'source_name' => 'موهيتو باشن', 'price_baisa' => 1200, 'display_tag' => 'بارد', 'description' => 'لمسة استوائية.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'المنعش الفوّار بالتوت', 'source_name' => 'موهيتو توت', 'price_baisa' => 1200, 'display_tag' => 'بارد', 'description' => 'عناق الغابات.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'المنعش الفوّار بالمانجو', 'source_name' => 'موهيتو مانجو', 'price_baisa' => 1200, 'display_tag' => 'بارد', 'description' => 'شمس الاستواء.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'المنعش الفوّار بالخوخ', 'source_name' => 'موهيتو خوخ', 'price_baisa' => 1200, 'display_tag' => 'بارد', 'description' => 'رقّة الخوخ.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'المنعش الفوّار بالفراولة', 'source_name' => 'موهيتو فراولة', 'price_baisa' => 1200, 'display_tag' => 'بارد', 'description' => 'حمرة الخجل.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'الشاي المثلج بالخوخ', 'source_name' => 'آيس تي خوخ', 'price_baisa' => 1600, 'display_tag' => 'بارد', 'description' => 'انتعاش الظهيرة.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'الشاي المثلج بالليمون', 'source_name' => 'آيس تي ليمون', 'price_baisa' => 1600, 'display_tag' => 'بارد', 'description' => 'الكلاسيكية المنعشة.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'الشاي المثلج بالنعناع', 'source_name' => 'آيس تي نعناع', 'price_baisa' => 1600, 'display_tag' => 'بارد', 'description' => 'أنفاس الجليد.', 'allergens' => []],
            ['category' => 'fresh', 'name' => 'كركديه', 'source_name' => null, 'price_baisa' => 1600, 'display_tag' => 'بارد', 'description' => 'ياقوت الجلسات.', 'allergens' => [], 'options' => [
                ['name' => 'بلا إضافة', 'price_baisa' => 1600],
                ['name' => 'توت', 'price_baisa' => 1800],
                ['name' => 'مانجو', 'price_baisa' => 1800],
                ['name' => 'خوخ', 'price_baisa' => 1800],
                ['name' => 'فراولة', 'price_baisa' => 1800],
                ['name' => 'باشن', 'price_baisa' => 1800],
            ]],
            ['category' => 'frozen', 'name' => 'الجميدة', 'source_name' => 'آيس كريم', 'price_baisa' => 1000, 'display_tag' => 'مثلّج', 'description' => 'برودة تعانق الروح.', 'allergens' => ['حليب']],
            ['category' => 'frozen', 'name' => 'مثلّجة القهوة', 'source_name' => 'أفوغاتو', 'price_baisa' => 1800, 'display_tag' => 'مثلّج', 'description' => 'إسبريسو يذوب على الجميدة.', 'allergens' => ['حليب']],
            ['category' => 'frozen', 'name' => 'المخفوق المثلّج بالشوكولاتة', 'source_name' => 'ميلك شيك', 'price_baisa' => 1600, 'display_tag' => 'مثلّج', 'description' => 'كثيف وبارد.', 'allergens' => ['حليب']],
            ['category' => 'frozen', 'name' => 'المجروش المثلّج بالتوت', 'source_name' => 'سلاش', 'price_baisa' => 800, 'display_tag' => 'مثلّج', 'description' => 'ثلج مجروش بنكهة التوت.', 'allergens' => []],
            ['category' => 'sweets', 'name' => 'كعكة العسل', 'source_name' => 'هني كيك', 'price_baisa' => 1500, 'display_tag' => 'شريحة', 'description' => 'رحيق الذهب.', 'allergens' => ['حليب', 'جلوتين', 'بيض']],
            ['category' => 'sweets', 'name' => 'كعكة الشوكولاتة', 'source_name' => 'تشوكليت كيك', 'price_baisa' => 1700, 'display_tag' => 'شريحة', 'description' => 'غيمة من الكاكاو.', 'allergens' => ['حليب', 'جلوتين', 'بيض']],
            ['category' => 'sweets', 'name' => 'كعكة البندق والشوكولاتة', 'source_name' => 'فيريرو كيك', 'price_baisa' => 1800, 'display_tag' => 'شريحة', 'description' => 'ترف البندق.', 'allergens' => ['حليب', 'جلوتين', 'بيض', 'مكسرات']],
            ['category' => 'sweets', 'name' => 'كعكة البسكويت الأسود', 'source_name' => 'أوريو كيك', 'price_baisa' => 2200, 'display_tag' => 'شريحة', 'description' => 'بهجة التناقض.', 'allergens' => ['حليب', 'جلوتين', 'بيض']],
            ['category' => 'sweets', 'name' => 'كعكة الجبن بالكراميل', 'source_name' => 'تشيز كيك لوتس', 'price_baisa' => 2400, 'display_tag' => 'شريحة', 'description' => 'سحر الكراميل.', 'allergens' => ['حليب', 'جلوتين', 'بيض']],
            ['category' => 'cold', 'name' => 'القهوة السوداء النقية', 'source_name' => 'آيس أمريكانو', 'price_baisa' => 1500, 'display_tag' => 'بارد', 'description' => 'صفاء ويقظة.', 'allergens' => []],
            ['category' => 'cold', 'name' => 'القهوة المخملية البيضاء', 'source_name' => 'آيس لاتيه', 'price_baisa' => 1700, 'display_tag' => 'بارد', 'description' => 'انسياب النعومة.', 'allergens' => ['حليب']],
            ['category' => 'cold', 'name' => 'القهوة الإسبانية المثلجة', 'source_name' => 'آيس سبانيش لاتيه', 'price_baisa' => 1800, 'display_tag' => 'بارد', 'description' => 'السعادة في طبقات.', 'allergens' => ['حليب']],
            ['category' => 'cold', 'name' => 'قهوة الفستق الفاخرة', 'source_name' => 'آيس فستق لاتيه', 'price_baisa' => 1800, 'display_tag' => 'بارد', 'description' => 'نسيم الفستق.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'cold', 'name' => 'القهوة الجوزاء التليدة', 'source_name' => 'آيس بندق لاتيه', 'price_baisa' => 1800, 'display_tag' => 'بارد', 'description' => 'سحر البندق في كأس.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'cold', 'name' => 'القهوة الوردية', 'source_name' => 'آيس ورد لاتيه', 'price_baisa' => 1800, 'display_tag' => 'بارد', 'description' => 'نسيم الأزهار.', 'allergens' => ['حليب']],
            ['category' => 'cold', 'name' => 'قهوة الكراميل', 'source_name' => 'آيس كراميل لاتيه', 'price_baisa' => 1800, 'display_tag' => 'بارد', 'description' => 'شلّال الذهب.', 'allergens' => ['حليب']],
            ['category' => 'cold', 'name' => 'التقطير اليدوي المختص', 'source_name' => 'V60', 'price_baisa' => 1800, 'display_tag' => 'بارد', 'description' => 'قطرات الندى.', 'allergens' => []],
            ['category' => 'cold', 'name' => 'قهوة اليوم المقطّرة', 'source_name' => 'كولد برو', 'price_baisa' => 1800, 'display_tag' => 'بارد', 'description' => 'طول الأناة يثمر ألذّ النكهات.', 'allergens' => []],
            ['category' => 'cold', 'name' => 'الزمرد الياباني', 'source_name' => 'آيس ماتشا لاتيه', 'price_baisa' => 1700, 'display_tag' => 'بارد', 'description' => 'روح الطبيعة في كأس.', 'allergens' => ['حليب']],
            ['category' => 'cold', 'name' => 'الزمرد الياباني بالفستق', 'source_name' => 'آيس ماتشا فستق', 'price_baisa' => 1900, 'display_tag' => 'بارد', 'description' => 'لقاء الشرق بالشرق.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'cold', 'name' => 'الزمرد الياباني بالبندق', 'source_name' => 'آيس ماتشا بندق', 'price_baisa' => 1900, 'display_tag' => 'بارد', 'description' => 'ترابية ساحرة.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'hot', 'name' => 'خلاصة البن', 'source_name' => 'إسبريسو', 'price_baisa' => 1400, 'display_tag' => 'ساخن', 'description' => 'جوهر القهوة في أصفى صوره.', 'allergens' => []],
            ['category' => 'hot', 'name' => 'القهوة السوداء النقية', 'source_name' => 'أمريكانو', 'price_baisa' => 1500, 'display_tag' => 'ساخن', 'description' => 'أصالة تتنفس.', 'allergens' => []],
            ['category' => 'hot', 'name' => 'القهوة المتزنة', 'source_name' => 'كورتادو', 'price_baisa' => 1500, 'display_tag' => 'ساخن', 'description' => 'عندما تتعادل الكفّتان.', 'allergens' => ['حليب']],
            ['category' => 'hot', 'name' => 'القهوة المخملية', 'source_name' => 'فلات وايت', 'price_baisa' => 1600, 'display_tag' => 'ساخن', 'description' => 'ملمس الحرير.', 'allergens' => ['حليب']],
            ['category' => 'hot', 'name' => 'القهوة المخملية البيضاء', 'source_name' => 'لاتيه', 'price_baisa' => 1700, 'display_tag' => 'ساخن', 'description' => 'عناق دافئ.', 'allergens' => ['حليب']],
            ['category' => 'hot', 'name' => 'القهوة الأندلسية', 'source_name' => 'سبانيش لاتيه', 'price_baisa' => 1800, 'display_tag' => 'ساخن', 'description' => 'حلاوة الأندلس.', 'allergens' => ['حليب']],
            ['category' => 'hot', 'name' => 'قهوة الفستق الفاخرة', 'source_name' => 'فستق لاتيه', 'price_baisa' => 1800, 'display_tag' => 'ساخن', 'description' => 'الترف الأخضر.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'hot', 'name' => 'القهوة الجوزاء التليدة', 'source_name' => 'بندق لاتيه', 'price_baisa' => 1800, 'display_tag' => 'ساخن', 'description' => 'دفء الأخشاب العطرية.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'hot', 'name' => 'القهوة الوردية', 'source_name' => 'ورد لاتيه', 'price_baisa' => 1800, 'display_tag' => 'ساخن', 'description' => 'عبق البساتين.', 'allergens' => ['حليب']],
            ['category' => 'hot', 'name' => 'قهوة الكراميل', 'source_name' => 'كراميل لاتيه', 'price_baisa' => 1800, 'display_tag' => 'ساخن', 'description' => 'عناق الحلاوة.', 'allergens' => ['حليب']],
            ['category' => 'hot', 'name' => 'التقطير اليدوي المختص', 'source_name' => 'V60', 'price_baisa' => 1800, 'display_tag' => 'ساخن', 'description' => 'طقوس القهوة.', 'allergens' => []],
            ['category' => 'hot', 'name' => 'الزمرد الياباني', 'source_name' => 'ماتشا لاتيه', 'price_baisa' => 1700, 'display_tag' => 'ساخن', 'description' => 'روح الطبيعة في كأس.', 'allergens' => ['حليب']],
            ['category' => 'hot', 'name' => 'الزمرد الياباني بالفستق', 'source_name' => 'ماتشا فستق', 'price_baisa' => 1900, 'display_tag' => 'ساخن', 'description' => 'لقاء الشرق بالشرق.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'hot', 'name' => 'الزمرد الياباني بالبندق', 'source_name' => 'ماتشا بندق', 'price_baisa' => 1900, 'display_tag' => 'ساخن', 'description' => 'ترابية ساحرة.', 'allergens' => ['حليب', 'مكسرات']],
            ['category' => 'tools', 'name' => 'بن إسبريسو برازيلي', 'source_name' => 'كاتوي الأحمر', 'price_baisa' => 6000, 'display_tag' => '٥٠٠ جرام', 'description' => 'شوكولاتة داكنة، وكراميل، ومكسرات.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'طقم التقطير اليدوي', 'source_name' => 'V60 سِت', 'price_baisa' => 8500, 'display_tag' => 'طقم', 'description' => 'مقطّرة وإبريق وفلاتر.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'المطحنة اليدوية', 'source_name' => 'هاند غرايندر', 'price_baisa' => 12000, 'display_tag' => 'أداة', 'description' => 'طحن متدرّج بيدك.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'صانعة الإسبريسو المحمولة', 'source_name' => 'بورتابل إسبريسو', 'price_baisa' => 25000, 'display_tag' => 'جهاز', 'description' => 'إسبريسو في المخيّم والرحلة.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'المكبس الهوائي', 'source_name' => 'إير برس', 'price_baisa' => 16000, 'display_tag' => 'أداة', 'description' => 'كوب نقي في دقيقتين.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'خفّاقة الحليب الكهربائية', 'source_name' => 'ميلك فروذر', 'price_baisa' => 4500, 'display_tag' => 'جهاز', 'description' => 'رغوة اللاتيه في البيت.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'الميزان الرقمي بالمؤقّت', 'source_name' => 'كوفي سكيل', 'price_baisa' => 9000, 'display_tag' => 'جهاز', 'description' => 'الغرام والثانية معاً.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'إبريق الرسم على القهوة', 'source_name' => 'لاتيه آرت بيتشر', 'price_baisa' => 5500, 'display_tag' => 'أداة', 'description' => 'أول قلب على وجه كوبك.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'الكوب الحراري الموسوم', 'source_name' => 'تمبلر بيرحاء', 'price_baisa' => 6500, 'display_tag' => 'هدية', 'description' => 'يحفظ الحرارة ست ساعات.', 'allergens' => []],
            ['category' => 'tools', 'name' => 'صندوق هدية بيرحاء', 'source_name' => 'جفت بوكس', 'price_baisa' => 10000, 'display_tag' => 'هدية', 'description' => 'بن وكوب وبطاقة إهداء.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'مجموعة بيسات عُمانية قديمة', 'source_name' => null, 'price_baisa' => 4000, 'display_tag' => 'عملات', 'description' => 'قطع من عهود مضت.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'طوابع بريدية عُمانية', 'source_name' => null, 'price_baisa' => 3500, 'display_tag' => 'طوابع', 'description' => 'تاريخ في مربع صغير.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'أعداد قديمة من مجلات الأطفال', 'source_name' => null, 'price_baisa' => 2000, 'display_tag' => 'مجلات', 'description' => 'ما قرأه آباؤكم صغاراً.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'مذياع قديم', 'source_name' => 'راديو', 'price_baisa' => 18000, 'display_tag' => 'تحفة', 'description' => 'صوت زمن آخر.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'آلة تصوير بالفيلم', 'source_name' => 'كاميرا فيلم', 'price_baisa' => 22000, 'display_tag' => 'تحفة', 'description' => 'صور تنتظر التحميض.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'مصباح الكاز', 'source_name' => 'فنر', 'price_baisa' => 7000, 'display_tag' => 'تحفة', 'description' => 'ضوء ليالي القرى.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'ساعة جيب قديمة', 'source_name' => null, 'price_baisa' => 12000, 'display_tag' => 'تحفة', 'description' => 'الوقت في راحة اليد.', 'allergens' => []],
            ['category' => 'antiques', 'name' => 'بطاقات بريدية قديمة', 'source_name' => 'بوستكارد', 'price_baisa' => 1500, 'display_tag' => 'بطاقات', 'description' => 'رسائل مدن الخليج.', 'allergens' => []],
            ['category' => 'books', 'name' => 'أسرار لا تنتهي', 'source_name' => null, 'author_name' => 'عبدالله العيسري', 'price_baisa' => 4000, 'display_tag' => 'مذكرات', 'description' => 'مع الشيخ ابن غابش رحمه الله.', 'allergens' => []],
            ['category' => 'books', 'name' => 'هرولة بين المغدر والدوح', 'source_name' => null, 'author_name' => 'عبدالله العيسري', 'price_baisa' => 4000, 'display_tag' => 'مذكرات', 'description' => 'مع الشيخ حمود بن حميد الصوافي حفظه الله.', 'allergens' => []],
            ['category' => 'books', 'name' => 'مذكرات فصيح بن عوف', 'source_name' => null, 'author_name' => 'عبدالله العيسري', 'price_baisa' => 3500, 'display_tag' => 'رواية', 'description' => 'سبعون طريقة لاكتساب الفصاحة.', 'allergens' => []],
            ['category' => 'books', 'name' => 'سلسلة الإجادة: الرياضيات', 'source_name' => null, 'price_baisa' => 3000, 'display_tag' => 'مدرسي', 'description' => 'مراجعة وتدريبات.', 'allergens' => []],
            ['category' => 'books', 'name' => 'سلسلة الإجادة: العلوم', 'source_name' => null, 'price_baisa' => 3000, 'display_tag' => 'مدرسي', 'description' => 'مراجعة وتدريبات.', 'allergens' => []],
            ['category' => 'books', 'name' => 'سلسلة الإجادة: اللغة العربية', 'source_name' => null, 'price_baisa' => 3000, 'display_tag' => 'مدرسي', 'description' => 'مراجعة وتدريبات.', 'allergens' => []],
            ['category' => 'books', 'name' => 'كليلة ودمنة', 'source_name' => null, 'author_name' => 'ابن المقفّع', 'price_baisa' => 2500, 'display_tag' => 'أدب', 'description' => 'حكمة على ألسنة الحيوان.', 'allergens' => []],
            ['category' => 'books', 'name' => 'حيّ بن يقظان', 'source_name' => null, 'author_name' => 'ابن طفيل', 'price_baisa' => 2000, 'display_tag' => 'أدب', 'description' => 'فتى يكتشف العالم وحده.', 'allergens' => []],
            ['category' => 'books', 'name' => 'صور من حياة الصحابة', 'source_name' => null, 'author_name' => 'عبد الرحمن رأفت الباشا', 'price_baisa' => 3500, 'display_tag' => 'سيرة', 'description' => 'رجال صنعوا التاريخ.', 'allergens' => []],
            ['category' => 'books', 'name' => 'الأيام', 'source_name' => null, 'author_name' => 'طه حسين', 'price_baisa' => 2500, 'display_tag' => 'سيرة', 'description' => 'طفولة صنعت عميداً.', 'allergens' => []],
            ['category' => 'books', 'name' => 'الرحيق المختوم', 'source_name' => null, 'author_name' => 'صفي الرحمن المباركفوري', 'price_baisa' => 4000, 'display_tag' => 'سيرة', 'description' => 'السيرة النبوية مرتّبة.', 'allergens' => []],
            ['category' => 'books', 'name' => 'العادات السبع للمراهقين الأكثر فاعلية', 'source_name' => null, 'author_name' => 'شون كوفي', 'price_baisa' => 4500, 'display_tag' => 'تطوير', 'description' => 'سبع عادات تغيّر المسار.', 'allergens' => []],
        ];

        $skuNumber = 1;
        $categoryPositions = [];

        return collect($products)->map(function (array $product) use (&$skuNumber, &$categoryPositions): array {
            $category = $product['category'];
            $categoryPositions[$category] = ($categoryPositions[$category] ?? 0) + 1;
            $options = $product['options'] ?? [['name' => 'قياسي', 'price_baisa' => $product['price_baisa']]];

            return [
                'category' => $category,
                'slug' => sprintf('%s-%02d', $category, $categoryPositions[$category]),
                'name' => $product['name'],
                'source_name' => $product['source_name'] ?? null,
                'author_name' => $product['author_name'] ?? null,
                'display_tag' => $product['display_tag'],
                'description' => $product['description'],
                'allergens' => $product['allergens'],
                'sort_order' => $categoryPositions[$category],
                'options' => collect($options)->map(function (array $option) use (&$skuNumber): array {
                    return [
                        'name' => $option['name'],
                        'sku' => sprintf('BYR-%04d', $skuNumber++),
                        'price_baisa' => $option['price_baisa'],
                    ];
                })->all(),
            ];
        })->all();
    }
}
