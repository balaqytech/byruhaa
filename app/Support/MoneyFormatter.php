<?php

namespace App\Support;

use App\Support\Money\MoneyFactory;

class MoneyFormatter
{
    public static function baisa(int $amountBaisa, ?string $currency = 'OMR'): string
    {
        $currency = strtoupper($currency ?: 'OMR');
        $amount = MoneyFactory::formatMinorUnits($amountBaisa, $currency);

        return $currency === 'OMR'
            ? 'OMR '.$amount
            : $amount.' '.$currency;
    }
}
