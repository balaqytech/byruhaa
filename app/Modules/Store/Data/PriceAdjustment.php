<?php

namespace App\Modules\Store\Data;

use App\Modules\Store\Enums\PriceAdjustmentType;
use InvalidArgumentException;

final readonly class PriceAdjustment
{
    /** @param array<string, bool|float|int|string|null> $metadata */
    public function __construct(
        public PriceAdjustmentType $type,
        public string $label,
        public int $unitAmountBaisa,
        public string $sourceType,
        public int|string|null $sourceId = null,
        public ?string $code = null,
        public int $priority = 0,
        public bool $stackable = true,
        public array $metadata = [],
    ) {
        if ($this->unitAmountBaisa <= 0) {
            throw new InvalidArgumentException('Price adjustment amounts must be positive.');
        }
    }

    /** @return array{type: string, label: string, unit_amount_baisa: int, source_type: string, source_id: int|string|null, code: string|null, priority: int, stackable: bool, metadata: array<string, bool|float|int|string|null>} */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'label' => $this->label,
            'unit_amount_baisa' => $this->unitAmountBaisa,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'code' => $this->code,
            'priority' => $this->priority,
            'stackable' => $this->stackable,
            'metadata' => $this->metadata,
        ];
    }
}
