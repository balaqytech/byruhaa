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
                Category::query()->updateOrCreate(
                    ['slug' => $slug],
                    [...$categoryData, 'is_active' => true],
                );
            }

            ProductOption::withoutEvents(function (): void {
                foreach ($this->products() as $productData) {
                    $product = Product::query()->updateOrCreate(
                        ['slug' => $productData['slug']],
                        [
                            'category_id' => Category::query()->where('slug', $productData['category'])->value('id'),
                            'name' => $productData['name'],
                            'source_name' => $productData['source_name'],
                            'author_name' => $productData['author_name'] ?? null,
                            'display_tag' => $productData['display_tag'],
                            'description' => $productData['short_description'],
                            'long_description' => $productData['long_description'],
                            'allergens' => $productData['allergens'],
                            'status' => ProductStatus::from($productData['status']),
                            'sort_order' => (int) substr($productData['slug'], -2),
                        ],
                    );

                    foreach ($this->optionsFor($productData) as $optionIndex => $optionData) {
                        ProductOption::query()->updateOrCreate([
                            'product_id' => $product->getKey(),
                            'sort_order' => $optionIndex,
                        ], [
                            'name' => $optionData['name'],
                            'sku' => $optionData['sku'],
                            'price_baisa' => $optionData['price_baisa'],
                            'member_price_baisa' => $optionData['member_price_baisa'],
                            'currency' => 'OMR',
                            'is_available' => $productData['status'] === ProductStatus::Active->value,
                            'is_default' => $optionIndex === 0 ? true : null,
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

    /** @return list<array<string, mixed>> */
    private function products(): array
    {
        $contents = file_get_contents(__DIR__.'/data/store-catalog.json');

        if ($contents === false) {
            throw new \RuntimeException('The store catalog fixture could not be read.');
        }

        $previouslyActiveContents = file_get_contents(__DIR__.'/data/previous-active-products.json');

        if ($previouslyActiveContents === false) {
            throw new \RuntimeException('The previous active products fixture could not be read.');
        }

        return [
            ...json_decode($contents, true, 512, JSON_THROW_ON_ERROR),
            ...json_decode($previouslyActiveContents, true, 512, JSON_THROW_ON_ERROR),
        ];
    }

    /** @return list<array{name: string, sku: string, price_baisa: int, member_price_baisa: int|null}> */
    private function optionsFor(array $productData): array
    {
        $baseOption = [
            'name' => 'قياسي',
            'sku' => $productData['sku'],
            'price_baisa' => $productData['price_baisa'] ?? 0,
            'member_price_baisa' => $productData['member_price_baisa'],
        ];

        if ($productData['sku'] !== 'FR-009') {
            return [$baseOption];
        }

        $baseOption['name'] = 'بلا إضافة';
        $flavours = ['توت', 'مانجو', 'خوخ', 'فراولة', 'باشن'];

        return [
            $baseOption,
            ...array_map(fn (string $flavour, int $index): array => [
                'name' => $flavour,
                'sku' => sprintf('FR-009-%02d', $index + 1),
                'price_baisa' => 1800,
                'member_price_baisa' => null,
            ], $flavours, array_keys($flavours)),
        ];
    }
}
