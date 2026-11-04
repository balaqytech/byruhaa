<?php

namespace App\Modules\Store\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Slimani\MediaManager\Models\File;

/**
 * @property int $id
 * @property string $name
 * @property string|null $short_name
 * @property string $slug
 * @property string|null $description
 * @property int|null $image_id
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'short_name', 'slug', 'description', 'image_id', 'sort_order', 'is_active'])]
class Category extends Model implements AuditableContract
{
    protected $table = 'store_categories';

    /** @use HasFactory<CategoryFactory> */
    use AuditableTrait, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $auditInclude = [
        'name',
        'short_name',
        'slug',
        'description',
        'image_id',
        'sort_order',
        'is_active',
    ];

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order');
    }

    /** @return BelongsTo<File, $this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
