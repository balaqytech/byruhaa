<?php

namespace App\Modules\Store\Models;

use App\Casts\MoneyBaisaCast;
use Brick\Money\Money;
use Database\Factories\ProductOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Slimani\MediaManager\Models\File;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $sku
 * @property int $price_baisa
 * @property string $currency
 * @property int|null $image_id
 * @property int $sort_order
 * @property bool $is_available
 * @property bool|null $is_default
 * @property-read Money $price
 */
#[Fillable(['product_id', 'name', 'sku', 'price', 'price_baisa', 'currency', 'image_id', 'sort_order', 'is_available', 'is_default'])]
class ProductOption extends Model
{
    protected $table = 'store_product_options';

    /** @use HasFactory<ProductOptionFactory> */
    use HasFactory;

    protected static function newFactory(): ProductOptionFactory
    {
        return ProductOptionFactory::new();
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'OMR',
        'sort_order' => 0,
        'is_available' => true,
        'is_default' => null,
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductOption $option): void {
            if (blank($option->product_id)) {
                return;
            }

            $otherOptions = self::query()
                ->where('product_id', $option->product_id)
                ->when($option->exists, fn ($query) => $query->whereKeyNot($option->id));

            if ($option->is_default !== true) {
                if (! (clone $otherOptions)->where('is_default', true)->exists()) {
                    throw ValidationException::withMessages([
                        'is_default' => __('ui.messages.store_default_option_required'),
                    ]);
                }

                $option->is_default = null;

                return;
            }

            $otherOptions->update(['is_default' => null]);
        });

        static::deleting(function (ProductOption $option): void {
            $isDefault = (bool) self::query()
                ->whereKey($option->getKey())
                ->value('is_default');

            if (! $isDefault) {
                return;
            }

            throw ValidationException::withMessages([
                'is_default' => __('ui.messages.store_default_option_cannot_be_deleted'),
            ]);
        });
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<File, $this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => MoneyBaisaCast::of('price_baisa'),
            'price_baisa' => 'integer',
            'sort_order' => 'integer',
            'is_available' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}
