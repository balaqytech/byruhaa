<?php

namespace App\Casts;

use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Money|null, Money|int|string|null>
 */
class MoneyBaisaCast implements CastsAttributes
{
    public function __construct(
        private ?string $amountColumn = null,
        private string $currencyColumn = 'currency',
    ) {}

    public static function of(string $amountColumn, string $currencyColumn = 'currency'): string
    {
        return self::class.':'.$amountColumn.','.$currencyColumn;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $amountColumn = $this->amountColumn($key);

        if (! array_key_exists($amountColumn, $attributes) || $attributes[$amountColumn] === null) {
            return null;
        }

        return MoneyFactory::fromMinor(
            (int) $attributes[$amountColumn],
            $this->currency($attributes),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $amountColumn = $this->amountColumn($key);

        if ($value === null || $value === '') {
            return [$amountColumn => null];
        }

        if ($value instanceof Money) {
            return [
                $amountColumn => MoneyFactory::toMinor($value),
                $this->currencyColumn => $value->getCurrency()->getCurrencyCode(),
            ];
        }

        if (is_int($value)) {
            return [$amountColumn => $value];
        }

        if (is_string($value)) {
            return [
                $amountColumn => MoneyFactory::decimalStringToMinorUnits($value, $this->currency($attributes)),
                $this->currencyColumn => $this->currency($attributes),
            ];
        }

        throw new InvalidArgumentException('Money values must be Money objects, integer minor units, or decimal strings.');
    }

    private function amountColumn(string $key): string
    {
        return $this->amountColumn ?: $key.'_baisa';
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function currency(array $attributes): string
    {
        $currency = $attributes[$this->currencyColumn] ?? config('money.default_currency', MoneyFactory::OMR);

        return strtoupper((string) $currency);
    }
}
