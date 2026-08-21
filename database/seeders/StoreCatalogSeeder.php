<?php

namespace Database\Seeders;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Seeder;

class StoreCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $slug => $data) {
            Category::query()->firstOrCreate(
                ['slug' => $slug],
                [...$data, 'is_active' => true],
            );
        }

        foreach ($this->products() as $productData) {
            $product = Product::query()->firstOrCreate(
                ['slug' => $productData['slug']],
                [
                    'category_id' => Category::query()->where('slug', $productData['category'])->value('id'),
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'status' => ProductStatus::Draft,
                    'sort_order' => $productData['sort_order'],
                    'is_featured' => $productData['is_featured'],
                    'featured_sort_order' => $productData['featured_sort_order'],
                ],
            );

            foreach ($productData['options'] as $optionData) {
                ProductOption::query()->firstOrCreate(
                    ['sku' => $optionData['sku']],
                    [
                        'product_id' => $product->getKey(),
                        'name' => $optionData['name'],
                        'price_baisa' => $optionData['price_baisa'],
                        'currency' => 'OMR',
                        'is_available' => true,
                        'is_default' => $optionData['is_default'],
                        'tracks_inventory' => false,
                        'stock_on_hand' => 0,
                        'sort_order' => $optionData['sort_order'],
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, array{name: string, sort_order: int}>
     */
    private function categories(): array
    {
        return [
            'frozen' => ['name' => 'الجميدة والمثلجات', 'sort_order' => 10],
            'refreshments' => ['name' => 'المنعش البارد', 'sort_order' => 20],
            'cold-coffee' => ['name' => 'قهوة باردة', 'sort_order' => 30],
            'matcha' => ['name' => 'ماتشا', 'sort_order' => 40],
            'baked' => ['name' => 'حلويات ومخبوزات', 'sort_order' => 50],
            'hot-coffee' => ['name' => 'قهوة ساخنة', 'sort_order' => 60],
            'manual-brew' => ['name' => 'التقطير اليدوي', 'sort_order' => 70],
            'byruha-special' => ['name' => 'مشروبات بيرحاء الخاصة', 'sort_order' => 80],
            'store' => ['name' => 'دكان بيرحاء', 'sort_order' => 90],
        ];
    }

    /**
     * @return list<array{
     *     category: string,
     *     slug: string,
     *     name: string,
     *     description: string,
     *     sort_order: int,
     *     is_featured: bool,
     *     featured_sort_order: int,
     *     options: list<array{name: string, sku: string, price_baisa: int, sort_order: int, is_default: bool}>
     * }>
     */
    private function products(): array
    {
        $products = [
            ['category' => 'hot-coffee', 'name' => 'إسبريسو', 'description' => 'جرعة مركزة وواضحة من القهوة المختصة.', 'options' => [['name' => 'قياسي', 'price_baisa' => 1400]]],
            ['category' => 'hot-coffee', 'name' => 'أمريكانو ساخنة', 'description' => 'إسبريسو يخفف بالماء الساخن لنكهة صافية.', 'options' => [['name' => 'قياسي', 'price_baisa' => 1500]]],
            ['category' => 'hot-coffee', 'name' => 'كورتادو', 'description' => 'توازن بين جرعة القهوة والحليب المبخر.', 'options' => [['name' => 'قياسي', 'price_baisa' => 1500]]],
            ['category' => 'hot-coffee', 'name' => 'فلات وايت', 'description' => 'قهوة مركزة مع حليب برغوة رقيقة وقوام ناعم.', 'options' => [['name' => 'قياسي', 'price_baisa' => 1600]]],
            ['category' => 'hot-coffee', 'name' => 'لاتيه ساخنة', 'description' => 'وصفة لاتيه دافئة بخيارات نكهات متعددة.', 'options' => [
                ['name' => 'كلاسيك', 'price_baisa' => 1700],
                ['name' => 'سبانيش', 'price_baisa' => 1800],
                ['name' => 'فستق', 'price_baisa' => 1800],
                ['name' => 'بندق', 'price_baisa' => 1800],
                ['name' => 'ورد', 'price_baisa' => 1800],
                ['name' => 'كراميل', 'price_baisa' => 1800],
            ]],
            ['category' => 'cold-coffee', 'name' => 'أمريكانو باردة', 'description' => 'إسبريسو فوق الثلج والماء البارد لانتعاش واضح.', 'options' => [['name' => 'قياسي', 'price_baisa' => 1500]]],
            ['category' => 'cold-coffee', 'name' => 'لاتيه باردة', 'description' => 'قهوة باردة مع الحليب والثلج وخيارات نكهات مختارة.', 'is_featured' => true, 'featured_sort_order' => 3, 'options' => [
                ['name' => 'كلاسيك', 'price_baisa' => 1700],
                ['name' => 'سبانيش', 'price_baisa' => 1800, 'is_default' => true],
                ['name' => 'فستق', 'price_baisa' => 1800],
                ['name' => 'بندق', 'price_baisa' => 1800],
                ['name' => 'ورد', 'price_baisa' => 1800],
                ['name' => 'كراميل', 'price_baisa' => 1800],
            ]],
            ['category' => 'manual-brew', 'name' => 'V60', 'description' => 'تقطير يدوي يبرز تفاصيل البن بوضوح.', 'options' => [
                ['name' => 'ساخنة', 'price_baisa' => 1800],
                ['name' => 'باردة', 'price_baisa' => 1800, 'is_default' => true],
            ]],
            ['category' => 'manual-brew', 'name' => 'كولد برو', 'description' => 'قهوة مختصة منقوعة ببطء في الماء البارد لساعات.', 'is_featured' => true, 'featured_sort_order' => 4, 'options' => [['name' => 'قياسي', 'price_baisa' => 1800]]],
            ['category' => 'matcha', 'name' => 'ماتشا لاتيه', 'description' => 'ماتشا يابانية مخفوقة بعناية مع الحليب.', 'options' => [
                ['name' => 'باردة', 'price_baisa' => 1700],
                ['name' => 'ساخنة', 'price_baisa' => 1700],
                ['name' => 'فستق باردة', 'price_baisa' => 1900],
                ['name' => 'فستق ساخنة', 'price_baisa' => 1900],
                ['name' => 'بندق باردة', 'price_baisa' => 1900],
                ['name' => 'بندق ساخنة', 'price_baisa' => 1900, 'is_default' => true],
            ]],
            ['category' => 'refreshments', 'name' => 'موهيتو', 'description' => 'مشروب فوار بالليمون والنعناع مع خيارات فاكهية.', 'is_featured' => true, 'featured_sort_order' => 2, 'options' => [
                ['name' => 'باشن', 'price_baisa' => 1200],
                ['name' => 'توت', 'price_baisa' => 1200],
                ['name' => 'مانجو', 'price_baisa' => 1200, 'is_default' => true],
                ['name' => 'خوخ', 'price_baisa' => 1200],
                ['name' => 'فراولة', 'price_baisa' => 1200],
            ]],
            ['category' => 'refreshments', 'name' => 'آيس تي', 'description' => 'شاي مثلج منعش بثلاث نكهات.', 'is_featured' => true, 'featured_sort_order' => 5, 'options' => [
                ['name' => 'خوخ', 'price_baisa' => 1600],
                ['name' => 'ليمون', 'price_baisa' => 1600, 'is_default' => true],
                ['name' => 'نعناع', 'price_baisa' => 1600],
            ]],
            ['category' => 'refreshments', 'name' => 'كركديه', 'description' => 'منقوع كركديه بلون غني ومذاق منعش.', 'options' => [['name' => 'قياسي', 'price_baisa' => 1600]]],
            ['category' => 'frozen', 'name' => 'جميدة', 'description' => 'مزيج مثلج ومنعش بملمس ناعم.', 'is_featured' => true, 'featured_sort_order' => 1, 'options' => [['name' => 'قياسي', 'price_baisa' => 1000]]],
            ['category' => 'baked', 'name' => 'كعكة الشوكولاتة', 'description' => 'طبقات غنية من الكيك وكريمة الشوكولاتة.', 'options' => [['name' => 'قياسي', 'price_baisa' => 0]]],
            ['category' => 'baked', 'name' => 'كعكة العسل', 'description' => 'طبقات هشة مع كريمة خفيفة ونكهة عسل.', 'options' => [['name' => 'قياسي', 'price_baisa' => 0]]],
            ['category' => 'baked', 'name' => 'كعكة فيريرو', 'description' => 'شوكولاتة فاخرة مع قطع البندق المقرمشة.', 'options' => [['name' => 'قياسي', 'price_baisa' => 0]]],
            ['category' => 'baked', 'name' => 'كعكة أوريو', 'description' => 'كيك الكاكاو مع كريمة الفانيليا وبسكويت أوريو.', 'is_featured' => true, 'featured_sort_order' => 6, 'options' => [['name' => 'قياسي', 'price_baisa' => 0]]],
            ['category' => 'baked', 'name' => 'تشيز كيك اللوتس', 'description' => 'جبن كريمي ناعم فوق قاعدة اللوتس.', 'options' => [['name' => 'قياسي', 'price_baisa' => 0]]],
            ['category' => 'store', 'name' => 'بن إسبريسو برازيلي', 'description' => 'بن برازيلي بإيحاءات الشوكولاتة والكراميل والمكسرات.', 'options' => [['name' => 'قياسي', 'price_baisa' => 6000]]],
            ['category' => 'byruha-special', 'name' => 'كرك بيرحاء', 'description' => 'كرك بيرحاء بوصفة المكان.', 'options' => [['name' => 'قياسي', 'sku' => 'BYR-0043', 'price_baisa' => 600]]],
            ['category' => 'byruha-special', 'name' => 'أفناء برو', 'description' => 'مشروب بيرحاء الخاص بنكهة عميقة ومنعشة.', 'options' => [['name' => 'قياسي', 'sku' => 'BYR-0044', 'price_baisa' => 2500]]],
            ['category' => 'byruha-special', 'name' => 'نسيم قرنان', 'description' => 'وصفة خاصة مستوحاة من نسيم قرنان.', 'options' => [['name' => 'قياسي', 'sku' => 'BYR-0045', 'price_baisa' => 2800]]],
        ];

        $sku = 1;

        return array_values(collect($products)->values()->map(function (array $product, int $index) use (&$sku): array {
            $options = array_values(collect($product['options'])->values()->map(function (array $option, int $optionIndex) use (&$sku): array {
                return [
                    'name' => $option['name'],
                    'sku' => $option['sku'] ?? sprintf('BYR-%04d', $sku++),
                    'price_baisa' => $option['price_baisa'],
                    'sort_order' => $optionIndex,
                    'is_default' => $option['is_default'] ?? $optionIndex === 0,
                ];
            })->values()->all());

            return [
                'category' => $product['category'],
                'slug' => 'catalogue-'.($index + 1),
                'name' => $product['name'],
                'description' => $product['description'],
                'sort_order' => $index + 1,
                'is_featured' => $product['is_featured'] ?? false,
                'featured_sort_order' => $product['featured_sort_order'] ?? 0,
                'options' => $options,
            ];
        })->values()->all());
    }
}
