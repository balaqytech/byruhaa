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
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
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
 * @property bool $is_featured
 * @property int $featured_sort_order
 */
#[Fillable(['category_id', 'name', 'slug', 'description', 'featured_image_id', 'status', 'sort_order', 'is_featured', 'featured_sort_order'])]
class Product extends Model implements AuditableContract
{
    protected $table = 'store_products';

    /** @use HasFactory<ProductFactory> */
    use AuditableTrait, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $auditInclude = [
        'category_id',
        'name',
        'slug',
        'description',
        'featured_image_id',
        'status',
        'sort_order',
        'is_featured',
        'featured_sort_order',
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => ProductStatus::Draft->value,
        'sort_order' => 0,
        'is_featured' => false,
        'featured_sort_order' => 0,
    ];

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

    public function isPublishable(): bool
    {
        $this->loadMissing(['category', 'options']);

        $defaults = $this->options->filter(fn (ProductOption $option): bool => $option->is_default === true);

        $default = $defaults->first();

        return $this->category?->is_active === true
            && $defaults->count() === 1
            && $default instanceof ProductOption
            && filled(trim($default->sku))
            && ! ProductOption::query()
                ->where('sku', $default->sku)
                ->whereKeyNot($default->getKey())
                ->exists()
            && $default->price_baisa > 0;
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
            'is_featured' => 'boolean',
            'featured_sort_order' => 'integer',
        ];
    }
}
