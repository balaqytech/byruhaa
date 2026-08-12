<?php

namespace App\Modules\Store\Models;

use App\Modules\Store\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Slimani\MediaManager\Models\File;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $featured_image_id
 * @property ProductStatus $status
 * @property int $sort_order
 */
#[Fillable(['category_id', 'name', 'slug', 'description', 'featured_image_id', 'status', 'sort_order'])]
class Product extends Model
{
    protected $table = 'store_products';

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => ProductStatus::Draft->value,
        'sort_order' => 0,
    ];

    protected static function booted(): void
    {
        static::created(function (Product $product): void {
            $product->options()->create([
                'name' => 'Standard',
                'sku' => 'STORE-'.$product->getKey().'-'.Str::upper(Str::random(5)),
                'price_baisa' => 0,
                'currency' => 'OMR',
                'sort_order' => 0,
                'is_available' => true,
                'is_default' => true,
            ]);
        });
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    /** @return HasOne<ProductOption, $this> */
    public function defaultOption(): HasOne
    {
        return $this->hasOne(ProductOption::class)->where('is_default', true);
    }

    /** @return BelongsTo<File, $this> */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'featured_image_id');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'sort_order' => 'integer',
        ];
    }
}
