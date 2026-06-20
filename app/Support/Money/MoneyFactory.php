<?php

namespace App\Support\Money;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MoneyFactory
{
    public const OMR = 'OMR';

    public const OMR_SCALE = 3;

    public static function fromMinor(int $minorUnits, string $currency = self::OMR): Money
    {
        return Money::ofMinor($minorUnits, strtoupper($currency));
    }

    public static function zero(string $currency = self::OMR): Money
    {
        return Money::zero(strtoupper($currency));
    }

    public static function toMinor(Money $money): int
    {
        return $money->getMinorAmount()->toInt();
    }

    /**
     * @param  iterable<int, Money>  $monies
     */
    public static function sum(iterable $monies, string $currency = self::OMR): Money
    {
        $total = self::zero($currency);

        foreach ($monies as $money) {
            $total = $total->plus($money);
        }

        return $total;
    }

    /**
     * @param  Collection<int, mixed>  $records
     */
    public static function sumMinor(Collection $records, string $column, string $currency = self::OMR): Money
    {
        return self::fromMinor((int) $records->sum($column), $currency);
    }

    public static function omrStringToBaisa(
        string $amount,
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): int {
        return self::decimalStringToMinorUnits($amount, self::OMR, $roundingMode);
    }

    public static function decimalStringToMinorUnits(
        string $amount,
        string $currency = self::OMR,
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): int {
        $currency = Currency::of(strtoupper($currency));
        $normalizedAmount = self::normalizeDecimalString($amount, $currency->getCurrencyCode());

        return BigDecimal::of($normalizedAmount)
            ->toScale($currency->getDefaultFractionDigits(), $roundingMode)
            ->getUnscaledValue()
            ->toInt();
    }

    public static function formatOmr(int $amountBaisa): string
    {
        return self::OMR.' '.self::formatMinorUnits($amountBaisa, self::OMR);
    }

    public static function format(Money $money): string
    {
        $currency = $money->getCurrency()->getCurrencyCode();
        $amount = self::formatMoneyAmount($money);

        return $currency === self::OMR
            ? self::OMR.' '.$amount
            : $amount.' '.$currency;
    }

    public static function formatMoneyAmount(Money $money): string
    {
        return self::formatMinorUnits(
            self::toMinor($money),
            $money->getCurrency()->getCurrencyCode(),
        );
    }

    public static function formatMinorUnits(int $minorUnits, string $currency = self::OMR): string
    {
        $currency = Currency::of(strtoupper($currency));
        $scale = $currency->getDefaultFractionDigits();
        $sign = $minorUnits < 0 ? '-' : '';
        $absoluteMinorUnits = abs($minorUnits);
        $divisor = 10 ** $scale;
        $majorUnits = intdiv($absoluteMinorUnits, $divisor);
        $minorRemainder = $absoluteMinorUnits % $divisor;

        if ($scale === 0) {
            return $sign.(string) $majorUnits;
        }

        return sprintf(
            '%s%d.%0'.$scale.'d',
            $sign,
            $majorUnits,
            $minorRemainder,
        );
    }

    private static function normalizeDecimalString(string $amount, string $currency): string
    {
        $normalized = str($amount)
            ->trim()
            ->upper()
            ->replace([',', ' '], '')
            ->replaceStart($currency, '')
            ->replaceEnd($currency, '')
            ->value();

        if ($normalized === '' || ! preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            throw new InvalidArgumentException('The money amount must be a decimal string.');
        }

        return $normalized;
    }
}
