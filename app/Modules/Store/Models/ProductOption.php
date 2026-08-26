<?php

namespace App\Modules\Store\Models;

use App\Casts\MoneyBaisaCast;
use Brick\Money\Money;
use Database\Factories\ProductOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
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
 * @property bool $tracks_inventory
 * @property int $stock_on_hand
 * @property-read Money $price
 */
#[Fillable(['product_id', 'name', 'sku', 'price', 'price_baisa', 'currency', 'image_id', 'sort_order', 'is_available', 'is_default', 'tracks_inventory', 'stock_on_hand'])]
class ProductOption extends Model implements AuditableContract
{
    protected $table = 'store_product_options';

    /** @use HasFactory<ProductOptionFactory> */
    use AuditableTrait, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $auditInclude = [
        'product_id',
        'name',
        'sku',
        'price_baisa',
        'currency',
        'image_id',
        'sort_order',
        'is_available',
        'is_default',
        'tracks_inventory',
        'stock_on_hand',
    ];

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
        'tracks_inventory' => false,
        'stock_on_hand' => 0,
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

    /** @return HasMany<InventoryMovement, $this> */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest();
    }

    /** @return HasMany<InventoryReservationItem, $this> */
    public function reservationItems(): HasMany
    {
        return $this->hasMany(InventoryReservationItem::class);
    }

    public function availableQuantity(): ?int
    {
        if (! $this->tracks_inventory) {
            return null;
        }

        return max(0, $this->stock_on_hand - $this->reservedQuantity());
    }

    public function reservedQuantity(): int
    {
        if (! $this->tracks_inventory) {
            return 0;
        }

        if ($this->relationLoaded('reservationItems')) {
            return (int) $this->reservationItems->sum('quantity');
        }

        return (int) $this->reservationItems()
            ->whereHas('reservation', fn ($query) => $query
                ->where('status', 'pending')
                ->where('expires_at', '>', now()))
            ->sum('quantity');
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
            'tracks_inventory' => 'boolean',
            'stock_on_hand' => 'integer',
        ];
    }
}
