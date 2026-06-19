<?php

namespace App\Models;

use Database\Factories\LedgerTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property string|null $source_type
 * @property int|null $source_id
 * @property string $description
 * @property Carbon $occurred_at
 * @property string $currency
 * @property int $total_baisa
 */
#[Fillable(['reference', 'source_type', 'source_id', 'description', 'occurred_at', 'currency', 'total_baisa'])]
class LedgerTransaction extends Model
{
    /** @use HasFactory<LedgerTransactionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'OMR',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'total_baisa' => 'integer',
        ];
    }
}
