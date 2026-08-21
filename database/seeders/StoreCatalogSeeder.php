<?php

namespace Database\Seeders;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class StoreCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->categories() as $slug => $data) {
                Category::query()->firstOrCreate(
                    ['slug' => $slug],
                    [...$data, 'is_active' => true],
                );
            }

            ProductOption::withoutEvents(function (): void {
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

                    if (! $product->wasRecentlyCreated) {
                        continue;
                    }

                    $defaultSku = collect($productData['options'])
                        ->filter(fn (array $option): bool => $option['is_default'] === true)
                        ->pluck('sku');

                    if ($defaultSku->count() !== 1) {
                        throw new LogicException(sprintf('Product [%s] must have exactly one default option.', $productData['slug']));
                    }

                    foreach ($productData['options'] as $optionData) {
                        ProductOption::query()->create([
                            'product_id' => $product->getKey(),
                            'name' => $optionData['name'],
                            'sku' => $optionData['sku'],
                            'price_baisa' => $optionData['price_baisa'],
                            'currency' => 'OMR',
                            'is_available' => true,
                            'is_default' => $optionData['is_default'] === true ? true : null,
                            'tracks_inventory' => false,
                            'stock_on_hand' => 0,
                            'sort_order' => $optionData['sort_order'],
                        ]);
                    }
                }
            });
        });
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
     *     options: list<array{name: string, sku: string, price_baisa: int, sort_order: int, is_default: bool|null}>
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

        /**
         * These identifiers are part of the catalogue contract. Do not derive them
         * from the order of the products array: products are intentionally merged
         * for the storefront while their existing SKU meanings must remain stable.
         *
         * @var array<int, array{slug: string, skus: list<string>, default_index: int}> $identifiers
         */
        $identifiers = [
            0 => ['slug' => 'catalogue-1', 'skus' => ['BYR-0001'], 'default_index' => 0],
            1 => ['slug' => 'catalogue-2', 'skus' => ['BYR-0002'], 'default_index' => 0],
            2 => ['slug' => 'catalogue-3', 'skus' => ['BYR-0003'], 'default_index' => 0],
            3 => ['slug' => 'catalogue-4', 'skus' => ['BYR-0004'], 'default_index' => 0],
            4 => ['slug' => 'catalogue-5', 'skus' => ['BYR-0005', 'BYR-0006', 'BYR-0007', 'BYR-0008', 'BYR-0009', 'BYR-0010'], 'default_index' => 1],
            5 => ['slug' => 'catalogue-11', 'skus' => ['BYR-0011'], 'default_index' => 0],
            6 => ['slug' => 'catalogue-12', 'skus' => ['BYR-0012', 'BYR-0013', 'BYR-0014', 'BYR-0015', 'BYR-0016', 'BYR-0017'], 'default_index' => 1],
            7 => ['slug' => 'catalogue-18', 'skus' => ['BYR-0018', 'BYR-0019'], 'default_index' => 1],
            8 => ['slug' => 'catalogue-20', 'skus' => ['BYR-0020'], 'default_index' => 0],
            9 => ['slug' => 'catalogue-21', 'skus' => ['BYR-0021', 'BYR-0022', 'BYR-0023', 'BYR-0024', 'BYR-0025', 'BYR-0026'], 'default_index' => 5],
            10 => ['slug' => 'catalogue-27', 'skus' => ['BYR-0027', 'BYR-0028', 'BYR-0029', 'BYR-0030', 'BYR-0031'], 'default_index' => 2],
            11 => ['slug' => 'catalogue-32', 'skus' => ['BYR-0032', 'BYR-0033', 'BYR-0034'], 'default_index' => 1],
            12 => ['slug' => 'catalogue-35', 'skus' => ['BYR-0035'], 'default_index' => 0],
            13 => ['slug' => 'catalogue-36', 'skus' => ['BYR-0036'], 'default_index' => 0],
            14 => ['slug' => 'catalogue-38', 'skus' => ['BYR-0038'], 'default_index' => 0],
            15 => ['slug' => 'catalogue-39', 'skus' => ['BYR-0039'], 'default_index' => 0],
            16 => ['slug' => 'catalogue-40', 'skus' => ['BYR-0040'], 'default_index' => 0],
            17 => ['slug' => 'catalogue-41', 'skus' => ['BYR-0041'], 'default_index' => 0],
            18 => ['slug' => 'catalogue-42', 'skus' => ['BYR-0042'], 'default_index' => 0],
            19 => ['slug' => 'catalogue-37', 'skus' => ['BYR-0037'], 'default_index' => 0],
            20 => ['slug' => 'kark-byruha', 'skus' => ['BYR-0043'], 'default_index' => 0],
            21 => ['slug' => 'afnaa-brew', 'skus' => ['BYR-0044'], 'default_index' => 0],
            22 => ['slug' => 'naseem-qarnan', 'skus' => ['BYR-0045'], 'default_index' => 0],
        ];

        return array_values(collect($products)->values()->map(function (array $product, int $index) use ($identifiers): array {
            $identifier = $identifiers[$index] ?? throw new LogicException(sprintf('Missing catalogue identifiers for product index [%d].', $index));

            if (count($product['options']) !== count($identifier['skus'])) {
                throw new LogicException(sprintf('SKU mapping does not match options for product [%s].', $identifier['slug']));
            }

            $options = array_values(collect($product['options'])->values()->map(function (array $option, int $optionIndex) use ($identifier): array {
                return [
                    'name' => $option['name'],
                    'sku' => $identifier['skus'][$optionIndex],
                    'price_baisa' => $option['price_baisa'],
                    'sort_order' => $optionIndex,
                    'is_default' => $optionIndex === $identifier['default_index'],
                ];
            })->values()->all());

            return [
                'category' => $product['category'],
                'slug' => $identifier['slug'],
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
